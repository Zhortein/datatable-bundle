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
use Zhortein\DatatableBundle\Definition\AjaxActionOptions;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\AjaxActionSuccessStrategy;
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

        $html = $this->createRendererWithCsrf()->renderBody($definition, $this->createResult());

        self::assertStringContainsString('<form', $html);
        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('action="/users/42/delete"', $html);
        self::assertStringContainsString('name="_method" value="DELETE"', $html);
        self::assertStringContainsString('name="_token" value="csrf-token-for-zhortein_datatable_action_delete"', $html);
        self::assertStringContainsString('type="submit"', $html);
        self::assertStringContainsString('Delete', $html);
        self::assertStringContainsString('btn btn-sm btn-danger', $html);
        self::assertStringContainsString('data-test="delete-user"', $html);
        self::assertStringNotContainsString('href="/users/42/delete"', $html);
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

        $html = $this->createRendererWithCsrf()->renderBody($definition, $this->createResult());

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

        $html = $this->createRendererWithCsrf()->render($definition, [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('<form', $html);
        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('action="/users/bulk-delete"', $html);
        self::assertStringContainsString('name="_method" value="POST"', $html);
        self::assertStringContainsString('name="_token" value="csrf-token-for-zhortein_datatable_action_bulk-delete"', $html);
        self::assertStringContainsString('Bulk delete', $html);
        self::assertStringNotContainsString('href="/users/bulk-delete"', $html);
    }

    public function test_it_renders_get_global_action_as_link_without_csrf_token(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addGlobalAction(
                name: 'create',
                route: 'app_user_create',
                label: 'Create',
                httpMethod: 'GET',
            )
        ;

        $html = $this->createRendererWithCsrf()->render($definition, [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('href="/users/create"', $html);
        self::assertStringContainsString('Create', $html);
        self::assertStringNotContainsString('name="_token"', $html);
        self::assertStringNotContainsString('name="_method"', $html);
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

        $html = $this->createRendererWithoutCsrf()->render($definition, [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('<form', $html);
        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('action="/users/bulk-delete"', $html);
        self::assertStringContainsString('name="_method" value="POST"', $html);
        self::assertStringContainsString('Bulk delete', $html);
        self::assertStringNotContainsString('name="_token"', $html);
    }

    public function test_csrf_token_id_uses_action_name(): void
    {
        $csrfTokenManager = new RecordingCsrfTokenManager();
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'archive',
                route: 'app_user_delete',
                label: 'Archive',
                httpMethod: 'POST',
                routeParameters: ['id' => 'e.id'],
            )
        ;

        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new CsrfActionTestUrlGenerator(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
            csrfTokenManager: $csrfTokenManager,
            actionVisibilityChecker: new AllowAllActionVisibilityChecker(),
        );

        $renderer->renderBody($definition, $this->createResult());

        self::assertSame('zhortein_datatable_action_archive', $csrfTokenManager->getLastTokenId());
    }

    public function test_it_renders_ajax_metadata_without_removing_the_classic_form_fallback(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'archive',
                route: 'app_user_delete',
                label: 'Archive',
                httpMethod: 'POST',
                routeParameters: ['id' => 'e.id'],
                ajax: new AjaxActionOptions(AjaxActionSuccessStrategy::RefreshRow),
            )
        ;

        $html = $this->createRendererWithCsrf()->renderBody($definition, $this->createResult());

        self::assertStringContainsString('method="post"', $html);
        self::assertStringContainsString('action="/users/42/delete"', $html);
        self::assertStringContainsString('name="_token" value="csrf-token-for-zhortein_datatable_action_archive"', $html);
        self::assertStringContainsString('data-turbo="false"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-ajax-action="true"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-ajax-action-name="archive"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-ajax-success-strategy="refresh_row"', $html);
        self::assertStringContainsString('data-action="submit->zhortein--datatable-bundle--datatable#executeAjaxAction"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-row-identifier="42"', $html);
    }

    public function test_classic_actions_do_not_render_ajax_metadata(): void
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

        $html = $this->createRendererWithCsrf()->renderBody($definition, $this->createResult());

        self::assertStringContainsString('href="/users/42"', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-ajax-action=', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-row-identifier=', $html);
        self::assertStringNotContainsString('#executeAjaxAction', $html);
    }

    public function test_it_renders_ajax_metadata_for_global_and_bulk_actions(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addGlobalAction(
                name: 'synchronize',
                route: 'app_user_create',
                label: 'Synchronize',
                ajax: new AjaxActionOptions(AjaxActionSuccessStrategy::None),
            )
            ->addBulkAction(
                name: 'bulk-delete',
                route: 'app_user_bulk_delete',
                label: 'Delete selected',
                httpMethod: 'DELETE',
                ajax: new AjaxActionOptions(AjaxActionSuccessStrategy::RemoveRow),
            )
        ;

        $html = $this->createRendererWithCsrf()->render($definition, [
            'columnVisibility' => false,
            'export' => false,
        ]);

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-ajax-action-name="synchronize"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-ajax-success-strategy="none"', $html);
        self::assertStringContainsString('data-action="click->zhortein--datatable-bundle--datatable#executeAjaxAction"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-ajax-action-name="bulk-delete"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-ajax-success-strategy="remove_row"', $html);
        self::assertStringContainsString('data-action="submit->zhortein--datatable-bundle--datatable#submitBulkAction"', $html);
        self::assertStringContainsString('name="_token" value="csrf-token-for-zhortein_datatable_action_bulk-delete"', $html);
    }

    private function createRendererWithCsrf(): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new CsrfActionTestUrlGenerator(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
            csrfTokenManager: new RecordingCsrfTokenManager(),
            actionVisibilityChecker: new AllowAllActionVisibilityChecker(),
        );
    }

    private function createRendererWithoutCsrf(): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new CsrfActionTestUrlGenerator(),
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

final class CsrfActionTestUrlGenerator implements UrlGeneratorInterface
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

        if ('app_user_create' === $name) {
            return '/users/create';
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
}

final class RecordingCsrfTokenManager implements CsrfTokenManagerInterface
{
    private ?string $lastTokenId = null;

    public function getToken(string $tokenId): CsrfToken
    {
        $this->lastTokenId = $tokenId;

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

    public function getLastTokenId(): ?string
    {
        return $this->lastTokenId;
    }
}
