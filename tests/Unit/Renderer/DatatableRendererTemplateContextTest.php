<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererTemplateContextTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_main_template_receives_expected_context(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'search' => true,
            'pageSize' => 50,
            'sortField' => 'e.email',
            'sortDirection' => 'asc',
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('id="zhortein-datatable-users"', $html);
        self::assertStringContainsString('data-zhortein-datatable-name-value="users"', $html);
        self::assertStringContainsString('data-zhortein-datatable-page-size-value="50"', $html);
        self::assertStringContainsString('data-zhortein-datatable-sort-field-value="e.email"', $html);
        self::assertStringContainsString('data-zhortein-datatable-sort-direction-value="asc"', $html);
        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('Email', $html);
    }

    public function test_row_and_cell_templates_receive_expected_context(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $definition = $this->createDefinition();
        $result = new DatatableResult(
            rows: [
                [
                    'e_email' => 'alice@example.test',
                    'e_enabled' => true,
                ],
            ],
            totalItems: 1,
        );

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('alice@example.test', $html);
        self::assertStringContainsString('text-center', $html);
        self::assertStringContainsString('Yes', $html);
    }

    public function test_filter_template_receives_expected_context(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('name="filters[email]"', $html);
        self::assertStringContainsString('placeholder="Search email"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email', type: 'string')
            ->addColumn('e.enabled', label: 'Enabled', type: 'boolean')
            ->addFilter(
                name: 'email',
                field: 'e.email',
                label: 'Email',
                type: FilterType::Text,
                placeholder: 'Search email',
            )
        ;

        return $definition;
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
