<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererDeclarativeTranslationTest extends TestCase
{
    use TranslatableRendererTestTrait;

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function localeProvider(): iterable
    {
        yield 'English' => ['en', [
            'Email address',
            'Sort by Email address',
            'Email filter',
            'Search an email address',
            'Enabled',
            'Disabled',
            'Status rule',
            'View user',
            'Open this user?',
            'Create user',
            'Create a new user?',
            'Delete selected',
            'Delete the selected users?',
        ]];

        yield 'French' => ['fr', [
            'Adresse e-mail',
            'Trier par Adresse e-mail',
            'Filtre par e-mail',
            'Rechercher une adresse e-mail',
            'Activé',
            'Désactivé',
            'Règle de statut',
            'Voir l’utilisateur',
            'Ouvrir cet utilisateur ?',
            'Créer un utilisateur',
            'Créer un nouvel utilisateur ?',
            'Supprimer la sélection',
            'Supprimer les utilisateurs sélectionnés ?',
        ]];
    }

    /**
     * @param list<string> $expectedTranslations
     */
    #[DataProvider('localeProvider')]
    public function test_it_translates_every_declarative_surface(
        string $locale,
        array $expectedTranslations,
    ): void {
        [$renderer] = $this->createRenderer($locale);
        $definition = $this->createTranslatedDefinition();

        $initialHtml = $renderer->render($definition, [
            'searchBuilder' => true,
            'filterLayout' => 'toolbar',
        ]);
        $headerHtml = $renderer->renderHeader($definition, [
            'filterLayout' => 'header',
        ]);
        $bodyHtml = $renderer->renderBody($definition, $this->createResult());

        $combinedHtml = $initialHtml.$headerHtml.$bodyHtml;

        foreach ($expectedTranslations as $translation) {
            self::assertStringContainsString($translation, $combinedHtml);
        }

        self::assertStringContainsString('data-choices=', $initialHtml);
        self::assertStringContainsString('Column label: '.$expectedTranslations[0], $bodyHtml);
    }

    public function test_it_uses_the_current_locale_for_each_render_and_ajax_fragment(): void
    {
        [$renderer, $translator] = $this->createRenderer('en');
        $definition = $this->createTranslatedDefinition();

        $englishInitialHtml = $renderer->render($definition);
        $englishFragmentHtml = $renderer->renderBody($definition, $this->createResult());

        $translator->setLocale('fr');

        $frenchInitialHtml = $renderer->render($definition);
        $frenchFragmentHtml = $renderer->renderBody($definition, $this->createResult());

        self::assertStringContainsString('Email address', $englishInitialHtml);
        self::assertStringContainsString('View user', $englishFragmentHtml);
        self::assertStringContainsString('Adresse e-mail', $frenchInitialHtml);
        self::assertStringContainsString('Voir l’utilisateur', $frenchFragmentHtml);
        self::assertStringNotContainsString('Email address', $frenchInitialHtml);
        self::assertStringNotContainsString('View user', $frenchFragmentHtml);
    }

    public function test_it_treats_declarative_text_as_literal_without_a_domain(): void
    {
        [$renderer] = $this->createRenderer('en');

        $definition = new DatatableDefinition('literal-users');
        $definition->addColumn('email', label: 'Already translated');

        $html = $renderer->render($definition);

        self::assertStringContainsString('Already translated', $html);
        self::assertStringNotContainsString('This must not replace a literal', $html);
    }

    private function createTranslatedDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('translated-users');

        $definition
            ->setTranslationDomain('datatable_test')
            ->setOption('identifier', 'e_id')
            ->addColumn(
                'e.email',
                label: 'datatable.column.email',
                template: '@ZhorteinDatatableTest/custom_translated_label_cell.html.twig',
            )
            ->addFilter(
                name: 'email',
                field: 'e.email',
                label: 'datatable.filter.email',
                type: FilterType::Text,
                placeholder: 'datatable.filter.email_placeholder',
            )
            ->addFilter(
                name: 'status',
                field: 'e.status',
                label: 'datatable.advanced.status',
                type: FilterType::Choice,
                choices: [
                    'datatable.choice.enabled' => 'enabled',
                    'datatable.choice.disabled' => 'disabled',
                ],
            )
            ->addAdvancedFilterField(
                name: 'status',
                field: 'e.status',
                label: 'datatable.advanced.status',
                type: FilterType::Choice,
                choices: [
                    'datatable.choice.enabled' => 'enabled',
                    'datatable.choice.disabled' => 'disabled',
                ],
            )
            ->addRowAction(
                name: 'view',
                route: 'app_user_show',
                label: 'datatable.action.view',
                confirmationMessage: 'datatable.confirmation.view',
                routeParameters: ['id' => 'e.id'],
            )
            ->addGlobalAction(
                name: 'create',
                route: 'app_user_create',
                label: 'datatable.action.create',
                confirmationMessage: 'datatable.confirmation.create',
            )
            ->addBulkAction(
                name: 'delete',
                route: 'app_user_bulk_delete',
                label: 'datatable.action.delete_selected',
                confirmationMessage: 'datatable.confirmation.delete_selected',
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

    /**
     * @return array{DatatableRenderer, Translator}
     */
    private function createRenderer(string $locale): array
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');
        $loader->addPath(__DIR__.'/templates', 'ZhorteinDatatableTest');

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $translator = $this->addTranslationExtension($twig, $locale);

        return [
            new DatatableRenderer(
                twig: $twig,
                urlGenerator: new DeclarativeTranslationUrlGenerator(),
                routeParameterResolver: new RowActionRouteParameterResolver(),
                actionVisibilityChecker: new AllowAllActionVisibilityChecker(),
            ),
            $translator,
        ];
    }
}

final class DeclarativeTranslationUrlGenerator implements UrlGeneratorInterface
{
    /**
     * @param array<mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string {
        if ('app_user_show' === $name) {
            return '/users/'.($parameters['id'] ?? '');
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
