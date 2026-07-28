<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Contract\ExportJobClockInterface;
use Zhortein\DatatableBundle\Contract\ExportJobDispatcherInterface;
use Zhortein\DatatableBundle\Contract\ExportJobIdentifierGeneratorInterface;
use Zhortein\DatatableBundle\Contract\ExportJobOwnerResolverInterface;
use Zhortein\DatatableBundle\Controller\DatatableExportJobController;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportJobStatus;
use Zhortein\DatatableBundle\Export\DatatableExportAuthorizationContext;
use Zhortein\DatatableBundle\Export\ExportLimitResolver;
use Zhortein\DatatableBundle\Export\Job\ExportArtifact;
use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;
use Zhortein\DatatableBundle\Export\Job\FixedExportJobExpiryPolicy;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobRepository;
use Zhortein\DatatableBundle\Export\Job\InMemoryExportJobResultStorage;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;

final class DatatableExportJobControllerTest extends TestCase
{
    public function test_submission_is_owner_bound_and_idempotently_returns_the_same_job(): void
    {
        $infrastructure = $this->createInfrastructure();
        $request = new Request(
            query: ['mode' => 'full'],
            server: [
                'HTTP_X_EXPORT_OWNER' => 'owner-1',
                'HTTP_IDEMPOTENCY_KEY' => 'request-1',
            ],
        );

        $first = $infrastructure->controller->submit($request, 'users', 'csv');
        $second = $infrastructure->controller->submit($request, 'users', 'csv');

        self::assertSame(202, $first->getStatusCode());
        self::assertSame(202, $second->getStatusCode());
        self::assertSame($this->decode($first), $this->decode($second));
        self::assertCount(2, $infrastructure->dispatcher->identifiers);
        self::assertSame(ExportJobStatus::Pending->value, $this->decode($first)['status']);
    }

    public function test_reusing_an_idempotency_key_for_another_request_is_rejected(): void
    {
        $infrastructure = $this->createInfrastructure();
        $first = new Request(
            query: ['mode' => 'full', 'search' => 'alice'],
            server: [
                'HTTP_X_EXPORT_OWNER' => 'owner-1',
                'HTTP_IDEMPOTENCY_KEY' => 'request-1',
            ],
        );
        $second = new Request(
            query: ['mode' => 'full', 'search' => 'bob'],
            server: [
                'HTTP_X_EXPORT_OWNER' => 'owner-1',
                'HTTP_IDEMPOTENCY_KEY' => 'request-1',
            ],
        );

        self::assertSame(202, $infrastructure->controller->submit($first, 'users')->getStatusCode());
        $response = $infrastructure->controller->submit($second, 'users');

        self::assertSame(409, $response->getStatusCode());
        self::assertSame('idempotency_conflict', $this->decode($response)['error']);
        self::assertCount(1, $infrastructure->dispatcher->identifiers);
    }

    public function test_cross_owner_status_lookup_is_indistinguishable_from_a_missing_job(): void
    {
        $infrastructure = $this->createInfrastructure();
        $infrastructure->controller->submit(new Request(
            query: ['mode' => 'full'],
            server: ['HTTP_X_EXPORT_OWNER' => 'owner-1'],
        ), 'users');

        $response = $infrastructure->controller->status(
            new Request(server: ['HTTP_X_EXPORT_OWNER' => 'owner-2']),
            'job_1234567890abcdef',
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('job_not_found', $this->decode($response)['error']);
    }

    public function test_download_rechecks_authorization_and_never_exposes_the_result_when_denied(): void
    {
        $checker = new MutableJobAuthorizationChecker(true);
        $infrastructure = $this->createInfrastructure($checker);
        $submitRequest = new Request(
            query: ['mode' => 'full'],
            server: ['HTTP_X_EXPORT_OWNER' => 'owner-1'],
        );
        $infrastructure->controller->submit($submitRequest, 'users');
        $this->completeStoredJob($infrastructure);
        $checker->granted = false;

        $response = $infrastructure->controller->download(
            new Request(server: ['HTTP_X_EXPORT_OWNER' => 'owner-1']),
            'job_1234567890abcdef',
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('authorization_denied', $this->decode($response)['error']);
        self::assertSame(2, $checker->calls);
    }

    public function test_completed_result_is_streamed_with_private_no_store_headers(): void
    {
        $infrastructure = $this->createInfrastructure();
        $infrastructure->controller->submit(new Request(
            query: ['mode' => 'full'],
            server: ['HTTP_X_EXPORT_OWNER' => 'owner-1'],
        ), 'users');
        $this->completeStoredJob($infrastructure);

        $response = $infrastructure->controller->download(
            new Request(server: ['HTTP_X_EXPORT_OWNER' => 'owner-1']),
            'job_1234567890abcdef',
        );

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame('attachment; filename="users.csv"', $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        self::assertSame("Email\nalice@example.test\n", $content);
    }

    public function test_expired_result_is_deleted_and_cannot_be_downloaded(): void
    {
        $infrastructure = $this->createInfrastructure();
        $infrastructure->controller->submit(new Request(
            query: ['mode' => 'full'],
            server: ['HTTP_X_EXPORT_OWNER' => 'owner-1'],
        ), 'users');
        $this->completeStoredJob($infrastructure);
        $infrastructure->clock->set(new \DateTimeImmutable('2026-07-28T10:00:00+00:00'));

        $response = $infrastructure->controller->download(
            new Request(server: ['HTTP_X_EXPORT_OWNER' => 'owner-1']),
            'job_1234567890abcdef',
        );

        self::assertSame(410, $response->getStatusCode());
        self::assertSame('job_expired', $this->decode($response)['error']);
        self::assertSame(
            ExportJobStatus::Expired,
            $infrastructure->repository
                ->find(new ExportJobIdentifier('job_1234567890abcdef'))
                ?->getStatus(),
        );
    }

    private function completeStoredJob(JobControllerInfrastructure $infrastructure): void
    {
        $identifier = new ExportJobIdentifier('job_1234567890abcdef');
        $job = $infrastructure->repository->find($identifier);
        self::assertNotNull($job);
        $running = $job->start($infrastructure->clock->now());
        $path = tempnam(sys_get_temp_dir(), 'datatable_job_controller_');
        self::assertIsString($path);
        file_put_contents($path, "Email\nalice@example.test\n");
        $artifact = new ExportArtifact($path, 'users.csv', 'text/csv; charset=UTF-8');
        $result = $infrastructure->storage->store(
            $identifier,
            $artifact,
            $infrastructure->clock->now(),
        );
        $artifact->delete();
        $infrastructure->repository->save($running->complete(
            $result,
            $infrastructure->clock->now(),
            $infrastructure->clock->now()->modify('+1 hour'),
        ));
    }

    private function createInfrastructure(
        ?MutableJobAuthorizationChecker $checker = null,
    ): JobControllerInfrastructure {
        $repository = new InMemoryExportJobRepository();
        $storage = new InMemoryExportJobResultStorage(8);
        $clock = new FixedJobControllerClock(new \DateTimeImmutable('2026-07-28T08:00:00+00:00'));
        $dispatcher = new RecordingJobDispatcher();
        $checker ??= new MutableJobAuthorizationChecker(true);
        $datatable = new JobControllerDatatable();
        $registry = new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): JobControllerDatatable => $datatable,
            ]),
            ['users' => JobControllerDatatable::class],
        );

        $controller = new DatatableExportJobController(
            enabled: true,
            definitionFactory: new DatatableDefinitionFactory($registry),
            requestFactory: new DatatableRequestFactory(new AdvancedFilterExpressionFactory()),
            providerRegistry: new DataProviderRegistry([
                'array' => new ArrayDataProvider(),
            ], 'array'),
            authorizationChecker: $checker,
            limitResolver: new ExportLimitResolver(100),
            repository: $repository,
            resultStorage: $storage,
            clock: $clock,
            expiryPolicy: new FixedExportJobExpiryPolicy(3600),
            identifierGenerator: new FixedJobIdentifierGenerator(),
            ownerResolver: new HeaderJobOwnerResolver(),
            dispatcher: $dispatcher,
        );

        return new JobControllerInfrastructure(
            controller: $controller,
            repository: $repository,
            storage: $storage,
            clock: $clock,
            dispatcher: $dispatcher,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(\Symfony\Component\HttpFoundation\JsonResponse $response): array
    {
        $content = $response->getContent();
        self::assertIsString($content);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}

final readonly class JobControllerInfrastructure
{
    public function __construct(
        public DatatableExportJobController $controller,
        public InMemoryExportJobRepository $repository,
        public InMemoryExportJobResultStorage $storage,
        public FixedJobControllerClock $clock,
        public RecordingJobDispatcher $dispatcher,
    ) {
    }
}

final class MutableJobAuthorizationChecker implements DatatableExportAuthorizationCheckerInterface
{
    public int $calls = 0;

    public function __construct(
        public bool $granted,
    ) {
    }

    public function isGranted(DatatableExportAuthorizationContext $context): bool
    {
        ++$this->calls;

        return $this->granted;
    }
}

final class HeaderJobOwnerResolver implements ExportJobOwnerResolverInterface
{
    public function resolve(Request $request): ?string
    {
        return $request->headers->get('X-Export-Owner');
    }
}

final class RecordingJobDispatcher implements ExportJobDispatcherInterface
{
    /**
     * @var list<ExportJobIdentifier>
     */
    public array $identifiers = [];

    public function dispatch(ExportJobIdentifier $identifier): void
    {
        $this->identifiers[] = $identifier;
    }
}

final class FixedJobIdentifierGenerator implements ExportJobIdentifierGeneratorInterface
{
    public function generate(): ExportJobIdentifier
    {
        return new ExportJobIdentifier('job_1234567890abcdef');
    }
}

final class FixedJobControllerClock implements ExportJobClockInterface
{
    public function __construct(
        private \DateTimeImmutable $now,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function set(\DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}

final class JobControllerDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('e.email', label: 'Email')
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['email' => 'alice@example.test'],
                ['email' => 'bob@example.test'],
            ])
        ;
    }
}
