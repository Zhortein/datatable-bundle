<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Contract\DatatableExportAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Contract\ExportRowCountProviderInterface;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Enum\ExportMode;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\DatatableExportAuthorizationContext;
use Zhortein\DatatableBundle\Export\ExportLimitResolver;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Renderer\DatatableSummaryRenderer;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;
use Zhortein\DatatableBundle\Tests\Unit\Renderer\TranslatableRendererTestTrait;

final class DatatableControllerTest extends TestCase
{
    use TranslatableRendererTestTrait;

    private function createTranslator(string $locale = 'en'): Translator
    {
        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            __DIR__.'/../../../translations/zhortein_datatable.en.yaml',
            'en',
            'zhortein_datatable',
        );
        $translator->addResource(
            'yaml',
            __DIR__.'/../../../translations/zhortein_datatable.fr.yaml',
            'fr',
            'zhortein_datatable',
        );

        return $translator;
    }

    public function test_full_csv_export_response_ignores_pagination(): void
    {
        $controller = $this->createController();

        $response = $controller->export(new Request([
            'page' => '1',
            'pageSize' => '2',
            'mode' => 'full',
        ]), 'users', 'csv');

        $content = (string) $response->getContent();

        self::assertStringContainsString('alice@example.test', $content);
        self::assertStringContainsString('bob@example.test', $content);
        self::assertStringContainsString('charlie@example.test', $content);
    }

    public function test_it_returns_rendered_fragments_from_provider_result(): void
    {
        $controller = $this->createController();

        $response = $controller->fragments(new Request([
            'page' => '1',
            'pageSize' => '2',
        ]), 'users');

        self::assertSame(200, $response->getStatusCode());

        $payload = $this->decodeResponse($response->getContent());

        self::assertArrayHasKey('header', $payload);
        self::assertIsString($payload['header']);
        self::assertStringContainsString('<thead', $payload['header']);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="header"', $payload['header']);
        self::assertArrayHasKey('body', $payload);
        self::assertArrayHasKey('pagination', $payload);
        self::assertArrayHasKey('summary', $payload);
        self::assertSame(1, $payload['page']);
        self::assertSame(2, $payload['pageSize']);
        self::assertSame(3, $payload['totalItems']);
        self::assertSame(3, $payload['filteredItems']);
        self::assertSame(2, $payload['totalPages']);
        self::assertSame('Showing 1 to 2 of 3 results.', $payload['summary']);
        self::assertIsString($payload['body']);
        self::assertStringContainsString('alice@example.test', $payload['body']);
        self::assertStringContainsString('bob@example.test', $payload['body']);
        self::assertStringNotContainsString('charlie@example.test', $payload['body']);
        self::assertStringNotContainsString('server-only-secret', (string) $response->getContent());
        self::assertIsString($payload['pagination']);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-page-param="2"', $payload['pagination']);
    }

    public function test_it_returns_header_fragment_with_column_visibility_state(): void
    {
        $controller = $this->createController();

        $response = $controller->fragments(new Request([
            'visibleColumns' => ['e.email'],
            'page' => '1',
            'pageSize' => '10',
        ]), 'users');

        $payload = $this->decodeResponse($response->getContent());

        self::assertIsString($payload['header']);
        self::assertStringContainsString('Email', $payload['header']);
        self::assertStringNotContainsString('Display name', $payload['header']);
    }

    public function test_it_applies_search_request_to_provider(): void
    {
        $controller = $this->createController();

        $response = $controller->fragments(new Request([
            'search' => 'charlie',
        ]), 'users');

        $payload = $this->decodeResponse($response->getContent());

        self::assertSame(3, $payload['totalItems']);
        self::assertSame(1, $payload['filteredItems']);
        self::assertSame('1 result found, filtered from 3 total.', $payload['summary']);
        self::assertIsString($payload['body']);
        self::assertStringContainsString('charlie@example.test', $payload['body']);
        self::assertStringNotContainsString('alice@example.test', $payload['body']);
    }

    public function test_it_renders_empty_fragments_when_provider_returns_no_rows(): void
    {
        $controller = $this->createController();

        $response = $controller->fragments(new Request([
            'search' => 'missing',
        ]), 'users');

        $payload = $this->decodeResponse($response->getContent());

        self::assertSame(3, $payload['totalItems']);
        self::assertSame(0, $payload['filteredItems']);
        self::assertSame(0, $payload['totalPages']);
        self::assertSame('No result.', $payload['summary']);
        self::assertIsString($payload['body']);
        self::assertStringContainsString('No data available.', $payload['body']);
    }

    public function test_it_returns_csv_export_response(): void
    {
        $controller = $this->createController();

        $response = $controller->export(new Request([
            'page' => '1',
            'pageSize' => '2',
        ]), 'users', 'csv');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertSame('attachment; filename="users.csv"', $response->headers->get('Content-Disposition'));

        $content = (string) $response->getContent();

        self::assertStringContainsString('Email', $content);
        self::assertStringContainsString('alice@example.test', $content);
        self::assertStringContainsString('bob@example.test', $content);
        self::assertStringNotContainsString('charlie@example.test', $content);
    }

    public function test_full_export_is_allowed_at_the_row_limit(): void
    {
        $response = $this->createController(
            exportLimitResolver: new ExportLimitResolver(3),
        )->export(new Request(['mode' => 'full']), 'users', 'csv');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('charlie@example.test', (string) $response->getContent());
    }

    public function test_full_export_is_rejected_before_writing_above_the_row_limit(): void
    {
        $response = $this->createController(
            exportLimitResolver: new ExportLimitResolver(2),
        )->export(new Request(['mode' => 'full']), 'users', 'csv');

        self::assertSame(413, $response->getStatusCode());
        self::assertSame(
            'This export exceeds the 2-row limit. Apply more filters or export the current page.',
            $response->getContent(),
        );
        $cacheControl = $response->headers->get('Cache-Control');

        self::assertIsString($cacheControl);
        self::assertStringContainsString('private', $cacheControl);
        self::assertStringContainsString('no-store', $cacheControl);
        self::assertStringNotContainsString('alice@example.test', (string) $response->getContent());
    }

    public function test_current_export_uses_the_actual_remaining_page_size_for_the_limit(): void
    {
        $response = $this->createController(
            exportLimitResolver: new ExportLimitResolver(1),
        )->export(new Request([
            'page' => '2',
            'pageSize' => '2',
            'mode' => 'current',
        ]), 'users', 'csv');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('charlie@example.test', (string) $response->getContent());
    }

    public function test_authorization_is_checked_with_isolated_export_context_before_data_loading(): void
    {
        $checker = new RecordingExportAuthorizationChecker(false);
        $request = new Request(
            query: ['mode' => 'full', 'search' => 'alice'],
            attributes: ['_route' => 'zhortein_datatable_export'],
        );
        $response = $this->createController(
            exportAuthorizationChecker: $checker,
        )->export($request, 'users', 'xlsx');

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('You are not allowed to export this datatable.', $response->getContent());
        self::assertNotNull($checker->context);
        self::assertSame('users', $checker->context->getDefinition()->getName());
        self::assertSame(ExportFormat::Xlsx, $checker->context->getFormat());
        self::assertSame(ExportMode::Full, $checker->context->getMode());
        self::assertSame('alice', $checker->context->getDatatableRequest()?->getSearchQuery());
        self::assertSame($request, $checker->context->getRequest());
        self::assertSame('users', $checker->context->getInstance());
        self::assertFalse($checker->context->isChildDatatable());
    }

    public function test_export_error_is_translated_in_french(): void
    {
        $response = $this->createController(
            exportLimitResolver: new ExportLimitResolver(2),
            translator: $this->createTranslator('fr'),
        )->export(new Request(['mode' => 'full']), 'users', 'csv');

        self::assertSame(413, $response->getStatusCode());
        self::assertSame(
            'Cet export dépasse la limite de 2 lignes. Affinez les filtres ou exportez la page courante.',
            $response->getContent(),
        );
    }

    public function test_provider_without_count_capability_is_rejected_before_loading_rows(): void
    {
        $provider = new NonCountableControllerTestProvider();
        $response = $this->createController(
            provider: $provider,
        )->export(new Request(['mode' => 'full']), 'users', 'csv');

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse($provider->getDataCalled);
        self::assertStringContainsString(
            'cannot safely determine the export size',
            (string) $response->getContent(),
        );
    }

    public function test_invalid_provider_count_is_rejected_before_loading_rows(): void
    {
        $provider = new InvalidCountControllerTestProvider();
        $response = $this->createController(
            provider: $provider,
        )->export(new Request(['mode' => 'full']), 'users', 'csv');

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse($provider->getDataCalled);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(false|string $content): array
    {
        self::assertIsString($content);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        return $payload;
    }

    private function createController(
        ?DatatableExportAuthorizationCheckerInterface $exportAuthorizationChecker = null,
        ?ExportLimitResolver $exportLimitResolver = null,
        ?Translator $translator = null,
        ?DataProviderInterface $provider = null,
    ): DatatableController {
        $translator ??= $this->createTranslator();
        $provider ??= new ArrayDataProvider();

        return new DatatableController(
            definitionFactory: new DatatableDefinitionFactory($this->createDatatableRegistry()),
            requestFactory: new DatatableRequestFactory(new AdvancedFilterExpressionFactory()),
            providerRegistry: new DataProviderRegistry([
                ArrayDataProvider::PROVIDER_NAME => $provider,
            ]),
            renderer: new DatatableRenderer($this->createTwigEnvironment()),
            exportWriterRegistry: new ExportWriterRegistry([
                CsvExportWriter::WRITER_NAME => new CsvExportWriter(),
            ]),
            summaryRenderer: new DatatableSummaryRenderer($translator),
            exportAuthorizationChecker: $exportAuthorizationChecker,
            exportLimitResolver: $exportLimitResolver,
            translator: $translator,
        );
    }

    private function createDatatableRegistry(): DatatableRegistry
    {
        $datatable = new ControllerTestDatatable();

        return new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): ControllerTestDatatable => $datatable,
            ]),
            ['users' => ControllerTestDatatable::class],
        );
    }

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $this->addTranslationExtension($twig);

        return $twig;
    }
}

final class RecordingExportAuthorizationChecker implements DatatableExportAuthorizationCheckerInterface
{
    public ?DatatableExportAuthorizationContext $context = null;

    public function __construct(
        private readonly bool $granted,
    ) {
    }

    public function isGranted(DatatableExportAuthorizationContext $context): bool
    {
        $this->context = $context;

        return $this->granted;
    }
}

final class NonCountableControllerTestProvider implements DataProviderInterface
{
    public bool $getDataCalled = false;

    public function supports(DatatableDefinition $definition): bool
    {
        return true;
    }

    public function getData(
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): DatatableResult {
        $this->getDataCalled = true;

        return new DatatableResult();
    }
}

final class InvalidCountControllerTestProvider implements DataProviderInterface, ExportRowCountProviderInterface
{
    public bool $getDataCalled = false;

    public function supports(DatatableDefinition $definition): bool
    {
        return true;
    }

    public function getData(
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): DatatableResult {
        $this->getDataCalled = true;

        return new DatatableResult();
    }

    public function countExportRows(
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): int {
        return -1;
    }
}

final class ControllerTestDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->addColumn('e.id', visible: false, sortable: false, searchable: false)
            ->addColumn('e.email', label: 'Email')
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                [
                    'id' => 1,
                    'email' => 'alice@example.test',
                    'sourceSecret' => 'server-only-secret',
                ],
                [
                    'id' => 2,
                    'email' => 'bob@example.test',
                ],
                [
                    'id' => 3,
                    'email' => 'charlie@example.test',
                ],
            ])
        ;
    }
}
