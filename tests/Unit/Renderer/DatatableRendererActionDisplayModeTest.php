<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ActionDisplayMode;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererActionDisplayModeTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_row_actions_inline_by_default(): void
    {
        $html = $this->renderWithMode(null);

        self::assertStringContainsString('btn-group btn-group-sm', $html);
        self::assertStringContainsString('href="/users/42/view"', $html);
        self::assertStringContainsString('View', $html);
        self::assertStringContainsString('href="/users/42/edit"', $html);
        self::assertStringContainsString('Edit', $html);
        self::assertStringNotContainsString('zhortein-datatable__row-actions-dropdown', $html);
        self::assertStringNotContainsString('zhortein-datatable__row-actions-list', $html);
        self::assertStringNotContainsString('dropdown-menu', $html);
    }

    public function test_it_renders_row_actions_inline_when_configured_on_definition(): void
    {
        $html = $this->renderWithMode(ActionDisplayMode::Inline);

        self::assertStringContainsString('btn-group btn-group-sm', $html);
        self::assertStringContainsString('href="/users/42/view"', $html);
        self::assertStringContainsString('href="/users/42/edit"', $html);
        self::assertStringNotContainsString('dropdown-menu', $html);
    }

    public function test_it_renders_row_actions_as_dropdown_when_configured_on_definition(): void
    {
        $html = $this->renderWithMode(ActionDisplayMode::Dropdown);

        self::assertStringContainsString('zhortein-datatable__row-actions-dropdown', $html);
        self::assertStringContainsString('dropdown-menu dropdown-menu-end', $html);
        self::assertStringContainsString('dropdown-item', $html);
        self::assertStringContainsString('href="/users/42/view"', $html);
        self::assertStringContainsString('View', $html);
        self::assertStringContainsString('href="/users/42/edit"', $html);
        self::assertStringContainsString('Edit', $html);
    }

    public function test_it_renders_row_actions_as_list_when_configured_on_definition(): void
    {
        $html = $this->renderWithMode(ActionDisplayMode::List);

        self::assertStringContainsString('zhortein-datatable__row-actions-list', $html);
        self::assertStringContainsString('d-flex flex-column gap-1', $html);
        self::assertStringContainsString('href="/users/42/view"', $html);
        self::assertStringContainsString('View', $html);
        self::assertStringContainsString('href="/users/42/edit"', $html);
        self::assertStringContainsString('Edit', $html);
        self::assertStringNotContainsString('dropdown-menu', $html);
    }

    public function test_runtime_option_overrides_definition_action_display_mode(): void
    {
        $definition = $this->createDefinition();
        $definition->setOption('rowActionDisplayMode', ActionDisplayMode::Dropdown->value);

        $html = $this->createRenderer()->renderBody(
            $definition,
            $this->createResult(),
            [
                'rowActionDisplayMode' => ActionDisplayMode::List->value,
            ],
        );

        self::assertStringContainsString('zhortein-datatable__row-actions-list', $html);
        self::assertStringNotContainsString('zhortein-datatable__row-actions-dropdown', $html);
    }

    public function test_unknown_display_mode_falls_back_to_inline(): void
    {
        $definition = $this->createDefinition();
        $definition->setOption('rowActionDisplayMode', 'unknown');

        $html = $this->createRenderer()->renderBody($definition, $this->createResult());

        self::assertStringContainsString('btn-group btn-group-sm', $html);
        self::assertStringNotContainsString('zhortein-datatable__row-actions-dropdown', $html);
        self::assertStringNotContainsString('zhortein-datatable__row-actions-list', $html);
    }

    private function renderWithMode(?ActionDisplayMode $mode): string
    {
        $options = [];

        if (null !== $mode) {
            $options['rowActionDisplayMode'] = $mode->value;
        }

        return $this->createRenderer()->renderBody(
            $this->createDefinition(),
            $this->createResult(),
            $options,
        );
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'view',
                route: 'app_user_show',
                label: 'View',
                icon: 'bi bi-eye',
                routeParameters: ['id' => 'e.id'],
            )
            ->addRowAction(
                name: 'edit',
                route: 'app_user_edit',
                label: 'Edit',
                icon: 'bi bi-pencil',
                routeParameters: ['id' => 'e.id'],
            )
        ;

        return $definition;
    }

    private function createResult(): DatatableResult
    {
        return new DatatableResult(
            rows: [
                [
                    'e_id' => 42,
                    'e_email' => 'alice@example.test',
                ],
            ],
            totalItems: 1,
        );
    }

    private function createRenderer(): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new ActionDisplayModeTestUrlGenerator(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
            actionVisibilityChecker: new AllowAllActionVisibilityChecker(),
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

final class ActionDisplayModeTestUrlGenerator implements UrlGeneratorInterface
{
    /**
     * @param array<mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string {
        $id = $parameters['id'] ?? null;

        if ('app_user_show' === $name && (is_string($id) || is_int($id))) {
            return '/users/'.$id.'/view';
        }

        if ('app_user_edit' === $name && (is_string($id) || is_int($id))) {
            return '/users/'.$id.'/edit';
        }

        return '/'.$name;
    }

    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }
}
