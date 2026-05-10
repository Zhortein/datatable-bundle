<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererGlobalActionVisibilityTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_hides_global_actions_when_visibility_checker_denies_them(): void
    {
        $urlGenerator = new GlobalActionCountingUrlGenerator();

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
            routeParameterResolver: new RowActionRouteParameterResolver(),
            actionVisibilityChecker: new DenyGlobalActionVisibilityChecker(),
        );

        $html = $renderer->render($this->createDefinition());

        self::assertStringNotContainsString('Create', $html);
        self::assertStringNotContainsString('/users/create', $html);
        self::assertSame(0, $urlGenerator->getGenerateCallCount());
    }

    public function test_it_renders_global_actions_when_visibility_checker_allows_them(): void
    {
        $urlGenerator = new GlobalActionCountingUrlGenerator();

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
            routeParameterResolver: new RowActionRouteParameterResolver(),
            actionVisibilityChecker: new AllowCreateGlobalActionVisibilityChecker(),
        );

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('href="/users/create"', $html);
        self::assertStringContainsString('Create', $html);
        self::assertStringNotContainsString('Bulk delete', $html);
        self::assertSame(1, $urlGenerator->getGenerateCallCount());
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addGlobalAction(
                name: 'create',
                route: 'app_user_create',
                label: 'Create',
            )
            ->addGlobalAction(
                name: 'bulk-delete',
                route: 'app_user_bulk_delete',
                label: 'Bulk delete',
                httpMethod: 'POST',
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

final class DenyGlobalActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition $action, ActionVisibilityContext $context): bool
    {
        return false;
    }
}

final class AllowCreateGlobalActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition $action, ActionVisibilityContext $context): bool
    {
        return 'create' === $action->getName();
    }
}

final class GlobalActionCountingUrlGenerator implements UrlGeneratorInterface
{
    private int $generateCallCount = 0;

    /**
     * @param array<mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string {
        ++$this->generateCallCount;

        return match ($name) {
            'app_user_create' => '/users/create',
            'app_user_bulk_delete' => '/users/bulk-delete',
            default => '/'.$name,
        };
    }

    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }

    public function getGenerateCallCount(): int
    {
        return $this->generateCallCount;
    }
}
