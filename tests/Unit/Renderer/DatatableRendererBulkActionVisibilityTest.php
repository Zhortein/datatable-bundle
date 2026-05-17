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
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableRendererBulkActionVisibilityTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_hides_bulk_actions_when_visibility_checker_denies_them(): void
    {
        $urlGenerator = new BulkActionCountingUrlGenerator();

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
            routeParameterResolver: new RowActionRouteParameterResolver(),
            actionVisibilityChecker: new DenyBulkActionVisibilityChecker(),
        );

        $html = $renderer->render($this->createDefinition());

        self::assertStringNotContainsString('Bulk delete', $html);
        self::assertStringNotContainsString('/users/bulk-delete', $html);
        // If all bulk actions are hidden, the selection checkbox column should also be hidden
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-target="selectAllCheckbox"', $html);
        self::assertSame(0, $urlGenerator->getGenerateCallCount());
    }

    public function test_it_renders_bulk_actions_when_visibility_checker_allows_them(): void
    {
        $urlGenerator = new BulkActionCountingUrlGenerator();

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
            routeParameterResolver: new RowActionRouteParameterResolver(),
            actionVisibilityChecker: new AllowDeleteBulkActionVisibilityChecker(),
        );

        $html = $renderer->render($this->createDefinition());

        self::assertStringContainsString('Bulk delete', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="selectAllCheckbox"', $html);
        self::assertSame(1, $urlGenerator->getGenerateCallCount());
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addBulkAction(
                name: 'bulk-delete',
                route: 'app_user_bulk_delete',
                label: 'Bulk delete',
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

final class DenyBulkActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition|BulkActionDefinition $action, ActionVisibilityContext $context): bool
    {
        return false;
    }
}

final class AllowDeleteBulkActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition|BulkActionDefinition $action, ActionVisibilityContext $context): bool
    {
        return 'bulk-delete' === $action->getName();
    }
}

final class BulkActionCountingUrlGenerator implements UrlGeneratorInterface
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
