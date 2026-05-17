<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class BulkActionRendererTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_does_not_render_selector_column_without_bulk_actions(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('email', label: 'Email');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($definition);

        self::assertStringNotContainsString('zhortein-datatable__selector-column', $html);
        self::assertStringNotContainsString('name="selected_rows[]"', $html);
        self::assertStringNotContainsString('data-action="zhortein--datatable-bundle--datatable#selectAll"', $html);
    }

    public function test_it_renders_selector_column_with_bulk_actions(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('email', label: 'Email');
        $definition->addBulkAction('delete', 'user_bulk_delete');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $result = new DatatableResult(
            rows: [
                ['id' => 123, 'email' => 'user1@example.com'],
                ['id' => 456, 'email' => 'user2@example.com'],
            ],
            page: 1,
            pageSize: 10,
            totalItems: 2,
            filteredItems: 2,
        );

        // Header rendering
        $headerHtml = $renderer->renderHeader($definition);
        self::assertStringContainsString('zhortein-datatable__selector-column', $headerHtml);
        self::assertStringContainsString('data-action="zhortein--datatable-bundle--datatable#selectAll"', $headerHtml);
        self::assertStringContainsString('aria-label="Select all"', $headerHtml);

        // Body rendering
        $bodyHtml = $renderer->renderBody($definition, $result);
        self::assertStringContainsString('zhortein-datatable__selector-column', $bodyHtml);
        self::assertStringContainsString('name="selected_rows[]"', $bodyHtml);
        self::assertStringContainsString('value="123"', $bodyHtml);
        self::assertStringContainsString('value="456"', $bodyHtml);
        self::assertStringContainsString('aria-label="Select row 123"', $bodyHtml);
        self::assertStringContainsString('aria-label="Select row 456"', $bodyHtml);
    }

    public function test_it_resolves_identifier_from_option(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('email', label: 'Email');
        $definition->addBulkAction('delete', 'user_bulk_delete');
        $definition->setOption('identifier', 'uuid');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $result = new DatatableResult(
            rows: [
                ['uuid' => 'abc-def', 'id' => 123, 'email' => 'user1@example.com'],
            ],
            page: 1,
            pageSize: 10,
            totalItems: 1,
            filteredItems: 1,
        );

        $bodyHtml = $renderer->renderBody($definition, $result);
        self::assertStringContainsString('value="abc-def"', $bodyHtml);
        self::assertStringNotContainsString('value="123"', $bodyHtml);
    }

    public function test_it_resolves_identifier_from_e_id_fallback(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('email', label: 'Email');
        $definition->addBulkAction('delete', 'user_bulk_delete');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $result = new DatatableResult(
            rows: [
                ['e_id' => 789, 'email' => 'user1@example.com'],
            ],
            page: 1,
            pageSize: 10,
            totalItems: 1,
            filteredItems: 1,
        );

        $bodyHtml = $renderer->renderBody($definition, $result);
        self::assertStringContainsString('value="789"', $bodyHtml);
    }

    public function test_it_updates_colspan_in_empty_body(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('email', label: 'Email');
        $definition->addBulkAction('delete', 'user_bulk_delete');

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderEmptyBody($definition);

        // 1 column + 1 selector column = colspan 2
        self::assertStringContainsString('colspan="2"', $html);
    }

    public function test_it_renders_bulk_action_toolbar(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addColumn('email', label: 'Email');
        $definition->addBulkAction('delete', 'user_bulk_delete', label: 'Delete selected', icon: 'fa fa-trash');

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $this->createUrlGeneratorStub(),
        );

        $html = $renderer->render($definition);

        self::assertStringContainsString('zhortein-datatable__bulk-actions', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="bulkToolbar"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="selectedCount"', $html);
        self::assertStringContainsString('Delete selected', $html);
        self::assertStringContainsString('fa fa-trash', $html);
        self::assertStringContainsString('disabled', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="bulkAction"', $html);
    }

    private function createUrlGeneratorStub(): UrlGeneratorInterface
    {
        return new class implements UrlGeneratorInterface {
            /**
             * @param array<mixed> $parameters
             */
            public function generate(
                string $name,
                array $parameters = [],
                int $referenceType = self::ABSOLUTE_PATH,
            ): string {
                return '/'.$name;
            }

            public function setContext(RequestContext $context): void
            {
            }

            public function getContext(): RequestContext
            {
                return new RequestContext();
            }
        };
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
