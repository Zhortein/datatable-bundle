<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextRequestResolver;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\CsvExportWriter;
use Zhortein\DatatableBundle\Export\ExportWriterRegistry;
use Zhortein\DatatableBundle\Factory\AdvancedFilterExpressionFactory;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Factory\DatatableRequestFactory;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Renderer\DatatableSummaryRenderer;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;
use Zhortein\DatatableBundle\Tests\Unit\Renderer\TranslatableRendererTestTrait;

final class DatatableControllerContextTransportTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_fragments_and_exports_use_the_restored_context(): void
    {
        $transport = new DatatableContextTransport('controller-test-secret');
        $controller = $this->createController($transport);
        $token = $transport->createToken(
            'context',
            'french-table',
            new DatatableContext(['locale' => 'fr'], ['locale']),
        );

        self::assertNotNull($token);

        $query = [
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER => 'french-table',
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER => $token,
        ];
        $fragmentResponse = $controller->fragments(new Request($query), 'context');
        $exportResponse = $controller->export(new Request($query), 'context');
        $fragmentContent = $fragmentResponse->getContent();

        self::assertIsString($fragmentContent);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($fragmentContent, true, flags: JSON_THROW_ON_ERROR);

        self::assertIsString($payload['body']);
        self::assertStringContainsString('fr', $payload['body']);
        self::assertStringNotContainsString('>en<', $payload['body']);
        self::assertStringContainsString('fr', (string) $exportResponse->getContent());
    }

    private function createController(DatatableContextTransport $transport): DatatableController
    {
        $datatable = new ContextTransportControllerDatatable();
        $registry = new DatatableRegistry(
            new ServiceLocator([
                'context' => static fn (): ContextTransportControllerDatatable => $datatable,
            ]),
            ['context' => ContextTransportControllerDatatable::class],
        );
        $twig = $this->createTwigEnvironment();
        $translator = $this->addTranslationExtension($twig);

        return new DatatableController(
            definitionFactory: new DatatableDefinitionFactory($registry),
            requestFactory: new DatatableRequestFactory(new AdvancedFilterExpressionFactory()),
            providerRegistry: new DataProviderRegistry([
                'context' => new ContextTransportDataProvider(),
            ], 'context'),
            renderer: new DatatableRenderer(
                twig: $twig,
                contextTransport: $transport,
            ),
            exportWriterRegistry: new ExportWriterRegistry([
                CsvExportWriter::WRITER_NAME => new CsvExportWriter(),
            ]),
            summaryRenderer: new DatatableSummaryRenderer($translator),
            contextRequestResolver: new DatatableContextRequestResolver($transport),
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

        return $twig;
    }
}

final class ContextTransportControllerDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setContext(new DatatableContext(['locale' => 'en'], ['locale']))
            ->addColumn('locale', label: 'Locale')
        ;
    }
}

final class ContextTransportDataProvider implements DataProviderInterface
{
    public function supports(DatatableDefinition $definition): bool
    {
        return 'context' === $definition->getName();
    }

    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult
    {
        return new DatatableResult(
            rows: [[
                'locale' => $definition->getContext()->get('locale'),
            ]],
            totalItems: 1,
            filteredItems: 1,
        );
    }
}
