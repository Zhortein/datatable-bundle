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
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererRowActionDropdownOverflowTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_dropdown_row_actions_register_overflow_actions(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->setOption('rowActionDisplayMode', 'dropdown')
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'view',
                route: 'app_user_show',
                label: 'View',
                routeParameters: ['id' => 'e.id'],
            )
        ;

        $html = $this->createRenderer()->renderBody(
            $definition,
            new DatatableResult(
                rows: [
                    [
                        'e_id' => 42,
                        'e_email' => 'alice@example.test',
                    ],
                ],
                totalItems: 1,
            ),
        );

        self::assertStringContainsString('zhortein-datatable__row-actions-dropdown', $html);
        self::assertStringContainsString('show.bs.dropdown->zhortein--datatable-bundle--datatable#allowDropdownOverflow', $html);
        self::assertStringContainsString('hidden.bs.dropdown->zhortein--datatable-bundle--datatable#restoreDropdownOverflow', $html);
        self::assertStringContainsString('data-bs-boundary="viewport"', $html);
    }

    private function createRenderer(): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new RowActionDropdownOverflowUrlGenerator(),
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

final class RowActionDropdownOverflowUrlGenerator implements UrlGeneratorInterface
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

        if (is_string($id) || is_int($id)) {
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
}
