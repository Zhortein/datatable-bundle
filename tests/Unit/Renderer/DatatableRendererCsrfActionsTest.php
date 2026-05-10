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
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererCsrfActionsTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_non_get_row_action_as_form_with_csrf_token(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'delete',
                route: 'app_user_delete',
                label: 'Delete',
                httpMethod: 'DELETE',
                className: 'btn btn-sm btn-danger',
                routeParameters: ['id' => 'e.id'],
                attributes: ['data-test' => 'delete-user'],
            )
        ;

        $result = new DatatableResult(
            rows: [
                [
                    'e_id' => 42,
                    'email' => 'alice@example.test',
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $this->createUrlGeneratorStub(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
            csrfTokenManager: $this->createCsrfTokenManagerStub(),
        );

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('<form', $html);
        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('action="/users/42/delete"', $html);
        self::assertStringContainsString('name="_method" value="DELETE"', $html);
        self::assertStringContainsString('name="_token" value="csrf-token-for-zhortein_datatable_action_delete"', $html);
        self::assertStringContainsString('type="submit"', $html);
        self::assertStringContainsString('Delete', $html);
        self::assertStringContainsString('btn btn-sm btn-danger', $html);
        self::assertStringContainsString('data-test="delete-user"', $html);
    }

    public function test_it_renders_get_row_action_as_link_without_csrf_token(): void
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

        $result = new DatatableResult(
            rows: [
                [
                    'e_id' => 42,
                    'email' => 'alice@example.test',
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $this->createUrlGeneratorStub(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
            csrfTokenManager: $this->createCsrfTokenManagerStub(),
        );

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('href="/users/42"', $html);
        self::assertStringContainsString('View', $html);
        self::assertStringNotContainsString('name="_token"', $html);
        self::assertStringNotContainsString('<form', $html);
    }

    public function test_it_renders_non_get_global_action_as_form_with_csrf_token(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addGlobalAction(
                name: 'bulk-delete',
                route: 'app_user_bulk_delete',
                label: 'Bulk delete',
                httpMethod: 'POST',
                className: 'btn btn-sm btn-danger',
            )
        ;

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $this->createUrlGeneratorStub(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
            csrfTokenManager: $this->createCsrfTokenManagerStub(),
        );

        $html = $renderer->render($definition);

        self::assertStringContainsString('<form', $html);
        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('action="/users/bulk-delete"', $html);
        self::assertStringContainsString('name="_method" value="POST"', $html);
        self::assertStringContainsString('name="_token" value="csrf-token-for-zhortein_datatable_action_bulk-delete"', $html);
        self::assertStringContainsString('Bulk delete', $html);
    }

    public function test_it_renders_non_get_action_without_token_when_csrf_manager_is_missing(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addGlobalAction(
                name: 'bulk-delete',
                route: 'app_user_bulk_delete',
                label: 'Bulk delete',
                httpMethod: 'POST',
            )
        ;

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: $this->createUrlGeneratorStub(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
        );

        $html = $renderer->render($definition);

        self::assertStringContainsString('<form', $html);
        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('action="/users/bulk-delete"', $html);
        self::assertStringNotContainsString('name="_token"', $html);
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
                $id = $parameters['id'] ?? null;

                if ('app_user_show' === $name && (is_string($id) || is_int($id))) {
                    return '/users/'.$id;
                }

                if ('app_user_delete' === $name && (is_string($id) || is_int($id))) {
                    return '/users/'.$id.'/delete';
                }

                if ('app_user_bulk_delete' === $name) {
                    return '/users/bulk-delete';
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
        };
    }

    private function createCsrfTokenManagerStub(): CsrfTokenManagerInterface
    {
        return new class implements CsrfTokenManagerInterface {
            public function getToken(string $tokenId): CsrfToken
            {
                return new CsrfToken($tokenId, 'csrf-token-for-'.$tokenId);
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
