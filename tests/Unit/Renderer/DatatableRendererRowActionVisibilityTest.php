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
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererRowActionVisibilityTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_hides_row_actions_when_visibility_checker_denies_them(): void
    {
        $urlGenerator = new CountingUrlGenerator();

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
            routeParameterResolver: new RowActionRouteParameterResolver(),
            actionVisibilityChecker: new DenyActionVisibilityChecker(),
        );

        $html = $renderer->renderBody($this->createDefinition(), $this->createResult());

        self::assertStringNotContainsString('View', $html);
        self::assertStringNotContainsString('/users/42', $html);
        self::assertSame(0, $urlGenerator->getGenerateCallCount());
    }

    public function test_it_renders_row_actions_when_visibility_checker_allows_them(): void
    {
        $urlGenerator = new CountingUrlGenerator();

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $urlGenerator,
            routeParameterResolver: new RowActionRouteParameterResolver(),
            actionVisibilityChecker: new AllowOnlyAliceActionVisibilityChecker(),
        );

        $html = $renderer->renderBody($this->createDefinition(), $this->createResult());

        self::assertStringContainsString('href="/users/42"', $html);
        self::assertStringContainsString('View', $html);
        self::assertStringNotContainsString('href="/users/43"', $html);
        self::assertSame(1, $urlGenerator->getGenerateCallCount());
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
                [
                    'e_id' => 43,
                    'e_email' => 'bob@example.test',
                ],
            ],
            totalItems: 2,
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

final class DenyActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition|BulkActionDefinition $action, ActionVisibilityContext $context): bool
    {
        return false;
    }
}

final class AllowOnlyAliceActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition|BulkActionDefinition $action, ActionVisibilityContext $context): bool
    {
        $row = $context->getRow();

        return is_array($row) && 'alice@example.test' === ($row['e_email'] ?? null);
    }
}

final class CountingUrlGenerator implements UrlGeneratorInterface
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

        $id = $parameters['id'] ?? null;

        if ('app_user_show' === $name && (is_string($id) || is_int($id))) {
            return '/users/'.$id;
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

    public function getGenerateCallCount(): int
    {
        return $this->generateCallCount;
    }
}
