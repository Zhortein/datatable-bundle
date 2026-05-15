<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ActionDisplayMode;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererListActionWidthTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_get_and_non_get_actions_full_width_in_list_mode(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->setOption('rowActionDisplayMode', ActionDisplayMode::List->value)
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'view',
                route: 'app_user_show',
                label: 'View',
                className: 'btn btn-sm btn-outline-primary',
                routeParameters: ['id' => 'e.id'],
            )
            ->addRowAction(
                name: 'delete',
                route: 'app_user_delete',
                label: 'Delete',
                httpMethod: 'DELETE',
                confirmationMessage: 'Delete this user?',
                className: 'btn btn-sm btn-outline-danger',
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

        self::assertStringContainsString('zhortein-datatable__row-actions-list', $html);
        self::assertStringContainsString('d-grid gap-1', $html);

        self::assertStringContainsString('class="btn btn-sm btn-outline-primary w-100 text-start"', $html);
        self::assertStringContainsString('class="w-100"', $html);
        self::assertStringContainsString('class="btn btn-sm btn-outline-danger w-100 text-start"', $html);

        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('name="_method" value="DELETE"', $html);
        self::assertStringContainsString('name="_token" value="csrf-token"', $html);
    }

    public function test_inline_mode_is_not_changed(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'delete',
                route: 'app_user_delete',
                label: 'Delete',
                httpMethod: 'DELETE',
                className: 'btn btn-sm btn-outline-danger',
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

        self::assertStringContainsString('btn-group btn-group-sm', $html);
        self::assertStringContainsString('class="d-inline"', $html);
        self::assertStringNotContainsString('zhortein-datatable__row-actions-list', $html);
        self::assertStringNotContainsString('w-100 text-start', $html);
    }

    private function createRenderer(): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new ListActionWidthUrlGenerator(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
            actionVisibilityChecker: new AllowAllActionVisibilityChecker(),
            csrfTokenManager: new ListActionWidthCsrfTokenManager(),
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

final class ListActionWidthUrlGenerator implements UrlGeneratorInterface
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

        if ('app_user_delete' === $name && (is_string($id) || is_int($id))) {
            return '/users/'.$id.'/delete';
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

final class ListActionWidthCsrfTokenManager implements CsrfTokenManagerInterface
{
    public function getToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, 'csrf-token');
    }

    public function refreshToken(string $tokenId): CsrfToken
    {
        return $this->getToken($tokenId);
    }

    public function removeToken(string $tokenId): ?string
    {
        return null;
    }

    public function isTokenValid(CsrfToken $token): bool
    {
        return true;
    }
}
