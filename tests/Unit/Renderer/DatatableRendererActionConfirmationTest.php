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

final class DatatableRendererActionConfirmationTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_confirmation_metadata_on_get_row_action(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'view',
                route: 'app_user_show',
                label: 'View',
                confirmationMessage: 'Open this user?',
                routeParameters: ['id' => 'e.id'],
            )
        ;

        $html = $this->createRenderer()->renderBody($definition, $this->createResult());

        self::assertStringContainsString('href="/users/42"', $html);
        self::assertStringContainsString('data-zhortein-datatable-confirmation-message="Open this user?"', $html);
    }

    public function test_it_renders_confirmation_metadata_on_non_get_row_action_form(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addRowAction(
                name: 'delete',
                route: 'app_user_delete',
                label: 'Delete',
                httpMethod: 'DELETE',
                confirmationMessage: 'Delete this user?',
                routeParameters: ['id' => 'e.id'],
            )
        ;

        $html = $this->createRenderer()->renderBody($definition, $this->createResult());

        self::assertStringContainsString('<form', $html);
        self::assertStringContainsString('action="/users/42/delete"', $html);
        self::assertStringContainsString('data-zhortein-datatable-confirmation-message="Delete this user?"', $html);
        self::assertStringContainsString('name="_method" value="DELETE"', $html);
    }

    public function test_it_renders_confirmation_metadata_on_global_action(): void
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.email', label: 'Email')
            ->addGlobalAction(
                name: 'create',
                route: 'app_user_create',
                label: 'Create',
                confirmationMessage: 'Create a new user?',
            )
        ;

        $html = $this->createRenderer()->render($definition);

        self::assertStringContainsString('href="/users/create"', $html);
        self::assertStringContainsString('data-zhortein-datatable-confirmation-message="Create a new user?"', $html);
    }

    public function test_it_does_not_render_confirmation_metadata_when_message_is_missing(): void
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

        self::assertStringNotContainsString('data-zhortein-datatable-confirmation-message', $html);
    }

    private function createRenderer(): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new ConfirmationTestUrlGenerator(),
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

final class ConfirmationTestUrlGenerator implements UrlGeneratorInterface
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
