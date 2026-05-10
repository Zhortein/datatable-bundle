<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Tests\Unit\Renderer\TranslatableRendererTestTrait;

final class DatatableControllerTest extends TestCase
{
    use TranslatableRendererTestTrait;

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

        self::assertArrayHasKey('body', $payload);
        self::assertArrayHasKey('pagination', $payload);
        self::assertArrayHasKey('summary', $payload);
        self::assertSame(1, $payload['page']);
        self::assertSame(2, $payload['pageSize']);
        self::assertSame(3, $payload['totalItems']);
        self::assertSame(3, $payload['filteredItems']);
        self::assertSame(2, $payload['totalPages']);
        self::assertSame('Showing 1 to 2 of 3 entries', $payload['summary']);
        self::assertIsString($payload['body']);
        self::assertStringContainsString('alice@example.test', $payload['body']);
        self::assertStringContainsString('bob@example.test', $payload['body']);
        self::assertStringNotContainsString('charlie@example.test', $payload['body']);
        self::assertIsString($payload['pagination']);
        self::assertStringContainsString('data-zhortein-datatable-page-param="2"', $payload['pagination']);
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
        self::assertSame('Showing 1 to 1 of 1 entries, filtered from 3 total entries', $payload['summary']);
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
        self::assertSame('Showing 0 entries', $payload['summary']);
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

    private function createController(): DatatableController
    {
        return new DatatableController(
            definitionFactory: new DatatableDefinitionFactory($this->createDatatableRegistry()),
            requestFactory: new DatatableRequestFactory(),
            providerRegistry: new DataProviderRegistry([
                ArrayDataProvider::PROVIDER_NAME => new ArrayDataProvider(),
            ]),
            renderer: new DatatableRenderer($this->createTwigEnvironment()),
            exportWriterRegistry: new ExportWriterRegistry([
                CsvExportWriter::WRITER_NAME => new CsvExportWriter(),
            ]),
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
