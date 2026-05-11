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
use Zhortein\DatatableBundle\Enum\ActionIconPosition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererActionIconTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_icon_before_action_label_by_default(): void
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
        ;

        $html = $this->createRenderer()->renderBody($definition, $this->createResult());

        self::assertStringContainsString('<span class="bi bi-eye me-1" aria-hidden="true"></span>', $html);
        self::assertMatchesRegularExpression('/bi bi-eye me-1.*<span>View<\/span>/s', $html);
    }

    public function test_it_renders_icon_after_action_label_when_configured(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'view',
                route: 'app_user_show',
                label: 'View',
                icon: 'bi bi-arrow-right',
                iconPosition: ActionIconPosition::After,
                routeParameters: ['id' => 'e.id'],
            )
        ;

        $html = $this->createRenderer()->renderBody($definition, $this->createResult());

        self::assertStringContainsString('<span class="bi bi-arrow-right ms-1" aria-hidden="true"></span>', $html);
        self::assertMatchesRegularExpression('/<span>View<\/span>.*bi bi-arrow-right ms-1/s', $html);
    }

    public function test_it_renders_action_without_icon(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'view',
                route: 'app_user_show',
                label: 'View',
                routeParameters: ['id' => 'e.id'],
            )
        ;

        $html = $this->createRenderer()->renderBody($definition, $this->createResult());

        self::assertStringContainsString('<span>View</span>', $html);
        self::assertStringNotContainsString('aria-hidden="true"', $html);
    }

    public function test_it_renders_global_action_icon(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addGlobalAction(
                name: 'create',
                route: 'app_user_create',
                label: 'Create user',
                icon: 'bi bi-plus-lg',
            )
        ;

        $html = $this->createRenderer()->render($definition, [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('bi bi-plus-lg me-1', $html);
        self::assertStringContainsString('<span>Create user</span>', $html);
    }

    private function createRenderer(): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new IconTestUrlGenerator(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
            actionVisibilityChecker: new AllowAllActionVisibilityChecker(),
        );
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

final class IconTestUrlGenerator implements UrlGeneratorInterface
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
            return '/users/'.$id;
        }

        if ('app_user_create' === $name) {
            return '/users/create';
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
