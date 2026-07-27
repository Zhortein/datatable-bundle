<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Attribute\AsTwigFunction;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Preference\DatatablePreference;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Preference\NullDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\Tests\Unit\Renderer\TranslatableRendererTestTrait;
use Zhortein\DatatableBundle\Twig\DatatableTwigExtension;

final class DatatableTwigExtensionTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_render_method_is_exposed_as_twig_function(): void
    {
        $reflectionMethod = new \ReflectionMethod(DatatableTwigExtension::class, 'renderDatatable');
        $attributes = $reflectionMethod->getAttributes(AsTwigFunction::class);

        self::assertCount(1, $attributes);

        $attribute = $attributes[0]->newInstance();

        self::assertSame('zhortein_datatable', $attribute->name);
        self::assertSame(['html'], $attribute->isSafe);
    }

    public function test_it_renders_datatable_by_name(): void
    {
        $extension = $this->createExtension();

        $html = $extension->renderDatatable('users');

        self::assertStringContainsString('id="zhortein-datatable-users"', $html);
        self::assertStringContainsString('data-controller="zhortein--datatable-bundle--datatable"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('No data available.', $html);
    }

    public function test_it_renders_datatable_with_runtime_options(): void
    {
        $extension = $this->createExtension();

        $html = $extension->renderDatatable('users', [
            'search' => true,
        ]);

        self::assertStringContainsString('id="zhortein-datatable-users"', $html);
        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-target="searchInput"', $html);
        self::assertStringContainsString('Email', $html);
    }

    public function test_it_applies_datatable_preferences_to_rendering_defaults(): void
    {
        $extension = $this->createExtension(
            preferenceProvider: new FixedDatatablePreferenceProvider(DatatablePreference::create(
                pageSize: 50,
                sortField: 'e.email',
                sortDirection: SortDirection::Desc,
                visibleColumns: ['e.email'],
                hiddenColumns: ['e.displayName'],
            )),
        );

        $html = $extension->renderDatatable('users');

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-page-size-value="50"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-field-value="e.email"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-value="desc"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-field-param="e.displayName"', $html);
    }

    public function test_runtime_options_override_datatable_preferences(): void
    {
        $extension = $this->createExtension(
            preferenceProvider: new FixedDatatablePreferenceProvider(DatatablePreference::create(
                pageSize: 50,
                sortField: 'e.email',
                sortDirection: SortDirection::Desc,
                visibleColumns: ['e.email'],
            )),
        );

        $html = $extension->renderDatatable('users', [
            'pageSize' => 10,
            'sortField' => 'e.displayName',
            'sortDirection' => 'asc',
            'visibleColumns' => ['e.displayName'],
            'columnVisibility' => false,
        ]);

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-page-size-value="10"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-field-value="e.displayName"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-direction-value="asc"', $html);
        self::assertStringContainsString('Display name', $html);
        self::assertStringNotContainsString('Email', $html);
    }

    public function test_it_applies_multi_column_sorting_preferences(): void
    {
        $extension = $this->createExtension(
            preferenceProvider: new FixedDatatablePreferenceProvider(DatatablePreference::create(
                sorts: [
                    SortCriterion::create('e.displayName'),
                    SortCriterion::create('e.email', SortDirection::Desc),
                ],
            )),
        );

        $html = $extension->renderDatatable('users');

        self::assertStringContainsString('priority 1 of 2', $html);
        self::assertStringContainsString('priority 2 of 2', $html);
        self::assertSame(1, substr_count($html, 'aria-sort='));
    }

    public function test_an_explicit_empty_runtime_sort_list_clears_the_preference(): void
    {
        $extension = $this->createExtension(
            preferenceProvider: new FixedDatatablePreferenceProvider(DatatablePreference::create(
                sorts: [
                    SortCriterion::create('e.displayName'),
                    SortCriterion::create('e.email', SortDirection::Desc),
                ],
            )),
        );

        $html = $extension->renderDatatable('users', [
            'sorts' => [],
        ]);

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sort-field-value=""', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-sorts-value="[]"', $html);
        self::assertStringNotContainsString('aria-sort=', $html);
    }

    private function createExtension(
        ?Environment $twig = null,
        ?DatatablePreferenceProviderInterface $preferenceProvider = null,
    ): DatatableTwigExtension {
        $twig ??= $this->createTwigEnvironment();
        $preferenceProvider ??= new NullDatatablePreferenceProvider();

        $datatable = new TwigExtensionTestDatatable();

        $registry = new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): TwigExtensionTestDatatable => $datatable,
            ]),
            ['users' => TwigExtensionTestDatatable::class],
        );

        return new DatatableTwigExtension(
            definitionFactory: new DatatableDefinitionFactory($registry),
            renderer: new DatatableRenderer($twig),
            preferenceProvider: $preferenceProvider,
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

final class TwigExtensionTestDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(\stdClass::class)
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email', label: 'Email')
            ->addColumn('e.displayName', label: 'Display name')
        ;
    }
}

final readonly class FixedDatatablePreferenceProvider implements DatatablePreferenceProviderInterface
{
    public function __construct(
        private DatatablePreference $preference,
    ) {
    }

    public function getPreference(string $datatableName): DatatablePreference
    {
        return $this->preference;
    }
}
