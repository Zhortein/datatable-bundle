<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Contract\ExportRowCountProviderInterface;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\DatatableExportAuthorizationContext;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Hierarchy\AllowAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableInstanceFactory;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableRequestResolver;
use Zhortein\DatatableBundle\Hierarchy\DenyAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Renderer\DatatableSummaryRenderer;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;
use Zhortein\DatatableBundle\Tests\Unit\Renderer\TranslatableRendererTestTrait;

final class DatatableControllerChildTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_returns_a_private_nested_datatable_shell_from_a_signed_request(): void
    {
        $transport = new DatatableContextTransport('controller-test-secret');
        $coordinates = $this->createCoordinates($transport);
        $response = $this->createController(
            $transport,
            new AllowAllChildDatatableAuthorizationChecker(),
        )->child(new Request($coordinates), 'order-lines');
        $content = $response->getContent();
        $cacheControl = $response->headers->get('Cache-Control');

        self::assertSame(200, $response->getStatusCode());
        self::assertIsString($content);
        self::assertIsString($cacheControl);
        self::assertStringContainsString('private', $cacheControl);
        self::assertStringContainsString('no-store', $cacheControl);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-name-value="order-lines"', $content);
        self::assertStringContainsString(
            sprintf('data-zhortein--datatable-bundle--datatable-instance-value="%s"', $coordinates[DatatableContextTransport::INSTANCE_QUERY_PARAMETER]),
            $content,
        );
        self::assertStringContainsString('_zd_instance=', html_entity_decode($content));
        self::assertStringContainsString('_zd_context=', html_entity_decode($content));
    }

    public function test_signed_child_fragments_use_the_restored_context(): void
    {
        $transport = new DatatableContextTransport('controller-test-secret');
        $response = $this->createController(
            $transport,
            new AllowAllChildDatatableAuthorizationChecker(),
        )->fragments(new Request($this->createCoordinates($transport)), 'order-lines');
        $content = $response->getContent();

        self::assertIsString($content);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        self::assertIsString($payload['body']);
        self::assertStringContainsString('42', $payload['body']);
    }

    public function test_unsigned_child_fragments_are_rejected(): void
    {
        $transport = new DatatableContextTransport('controller-test-secret');
        $coordinates = $this->createCoordinates($transport);
        unset($coordinates[DatatableContextTransport::CONTEXT_QUERY_PARAMETER]);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(sprintf('The "%s" query parameter must be a non-empty string.', DatatableContextTransport::CONTEXT_QUERY_PARAMETER));

        $this->createController(
            $transport,
            new AllowAllChildDatatableAuthorizationChecker(),
        )->fragments(new Request($coordinates), 'order-lines');
    }

    public function test_the_child_endpoint_replays_authorization(): void
    {
        $transport = new DatatableContextTransport('controller-test-secret');

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Access to the child datatable was denied.');

        $this->createController(
            $transport,
            new DenyAllChildDatatableAuthorizationChecker(),
        )->child(new Request($this->createCoordinates($transport)), 'order-lines');
    }

    public function test_signed_child_export_exposes_restored_context_to_export_authorization(): void
    {
        $transport = new DatatableContextTransport('controller-test-secret');
        $checker = new ChildExportAuthorizationChecker();
        $coordinates = $this->createCoordinates($transport);
        $response = $this->createController(
            $transport,
            new AllowAllChildDatatableAuthorizationChecker(),
            $checker,
        )->export(new Request($coordinates), 'order-lines');

        self::assertSame(200, $response->getStatusCode());
        self::assertNotNull($checker->context);
        self::assertTrue($checker->context->isChildDatatable());
        self::assertSame(
            $coordinates[DatatableContextTransport::INSTANCE_QUERY_PARAMETER],
            $checker->context->getInstance(),
        );
        self::assertSame(42, $checker->context->getDatatableContext()->get('orderId'));
    }

    private function createController(
        DatatableContextTransport $transport,
        ChildDatatableAuthorizationCheckerInterface $authorizationChecker,
        ?DatatableExportAuthorizationCheckerInterface $exportAuthorizationChecker = null,
    ): DatatableController {
        $datatable = new ChildEndpointDatatable();
        $registry = new DatatableRegistry(
            new ServiceLocator([
                'order-lines' => static fn (): ChildEndpointDatatable => $datatable,
            ]),
            ['order-lines' => ChildEndpointDatatable::class],
        );
        $twig = $this->createTwigEnvironment();
        $translator = $this->addTranslationExtension($twig);
        $instanceFactory = new ChildDatatableInstanceFactory();

        return new DatatableController(
            definitionFactory: new DatatableDefinitionFactory($registry),
            requestFactory: new DatatableRequestFactory(new AdvancedFilterExpressionFactory()),
            providerRegistry: new DataProviderRegistry([
                'child' => new ChildEndpointDataProvider(),
            ], 'child'),
            renderer: new DatatableRenderer(
                twig: $twig,
                contextTransport: $transport,
            ),
            exportWriterRegistry: new ExportWriterRegistry([
                CsvExportWriter::WRITER_NAME => new CsvExportWriter(),
            ]),
            summaryRenderer: new DatatableSummaryRenderer($translator),
            contextRequestResolver: new DatatableContextRequestResolver($transport),
            childRequestResolver: new ChildDatatableRequestResolver(
                $transport,
                $instanceFactory,
                $authorizationChecker,
            ),
            exportAuthorizationChecker: $exportAuthorizationChecker,
        );
    }

    /**
     * @return array<string, string>
     */
    private function createCoordinates(DatatableContextTransport $transport): array
    {
        $instance = new ChildDatatableInstanceFactory()->create(
            'orders',
            'active-orders',
            'order-lines',
            42,
            1,
        );

        return [
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => $instance,
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => $transport->createRequiredToken(
                'order-lines',
                $instance,
                new DatatableContext(['orderId' => 42], ['orderId']),
            ),
        ];
    }

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');

        return new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);
    }
}

final class ChildEndpointDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setContext(new DatatableContext(['orderId' => null], ['orderId']))
            ->addColumn('orderId', label: 'Order identifier')
        ;
    }
}

final class ChildEndpointDataProvider implements DataProviderInterface, ExportRowCountProviderInterface
{
    public function supports(DatatableDefinition $definition): bool
    {
        return 'order-lines' === $definition->getName();
    }

    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult
    {
        return new DatatableResult(
            rows: [[
                'orderId' => $definition->getContext()->get('orderId'),
            ]],
            totalItems: 1,
            filteredItems: 1,
        );
    }

    public function countExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): int {
        return 1;
    }
}

final class ChildExportAuthorizationChecker implements DatatableExportAuthorizationCheckerInterface
{
    public ?DatatableExportAuthorizationContext $context = null;

    public function isGranted(DatatableExportAuthorizationContext $context): bool
    {
        $this->context = $context;

        return true;
    }
}
