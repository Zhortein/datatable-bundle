<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererSearchBuilderTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_does_not_render_search_builder_by_default(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition());

        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-target="searchBuilder"', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-search-builder-value="true"', $html);
    }

    public function test_it_renders_search_builder_when_enabled(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'searchBuilder' => true,
        ]);

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="searchBuilder"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-search-builder-value="true"', $html);
        self::assertStringContainsString('Search Builder', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="searchBuilderConditions"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="searchBuilderConditionTemplate"', $html);
    }

    public function test_it_renders_search_builder_toggle_button_in_toolbar(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'searchBuilder' => true,
        ]);

        self::assertStringContainsString('data-bs-toggle="collapse"', $html);
        self::assertStringContainsString('data-bs-target="#zhortein-datatable-users-search-builder"', $html);
        self::assertStringContainsString('Search Builder', $html);
    }

    public function test_it_renders_operators_and_labels_as_data_attributes(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'searchBuilder' => true,
        ]);

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-operators-value', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-operator-labels-value', $html);

        // Check for some operators in the JSON encoded attributes
        self::assertStringContainsString('eq', $html);
        self::assertStringContainsString('neq', $html);
        self::assertStringContainsString('contains', $html);
    }

    public function test_it_renders_available_fields_in_template(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->render($this->createDefinition(), [
            'searchBuilder' => true,
        ]);

        self::assertStringContainsString('<option value="email"', $html);
        self::assertStringContainsString('User Email', $html);
        self::assertStringContainsString('data-type="text"', $html);

        self::assertStringContainsString('<option value="status"', $html);
        self::assertStringContainsString('User Status', $html);
        self::assertStringContainsString('data-type="choice"', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addAdvancedFilterField(
                name: 'email',
                field: 'e.email',
                label: 'User Email',
                type: FilterType::Text,
            )
            ->addAdvancedFilterField(
                name: 'status',
                field: 'e.status',
                label: 'User Status',
                type: FilterType::Choice,
                choices: [
                    'Enabled' => 'enabled',
                    'Disabled' => 'disabled',
                ],
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
