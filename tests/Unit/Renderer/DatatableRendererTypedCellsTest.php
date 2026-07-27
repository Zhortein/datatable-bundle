<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Contract\EnumPresentationResolverInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\EnumPresentation\DefaultEnumPresentationResolver;
use Zhortein\DatatableBundle\EnumPresentation\EnumPresentation;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererTypedCellsTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_string_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'string', value: 'Alice');

        self::assertStringContainsString('Alice', $html);
    }

    public function test_it_renders_numeric_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'numeric', value: 1234);

        self::assertStringContainsString('1234', $html);
    }

    public function test_it_renders_boolean_true_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'boolean', value: true);

        self::assertStringContainsString('text-bg-success', $html);
        self::assertStringContainsString('Yes', $html);
    }

    public function test_it_renders_boolean_false_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'boolean', value: false);

        self::assertStringContainsString('text-bg-secondary', $html);
        self::assertStringContainsString('No', $html);
    }

    public function test_it_renders_datetime_cell_template(): void
    {
        $html = $this->renderSingleColumn(
            type: 'datetime',
            value: new \DateTimeImmutable('2026-05-09 14:30:00'),
        );

        self::assertStringContainsString('2026', $html);
        self::assertStringNotContainsString('DateTimeImmutable', $html);
    }

    public function test_it_renders_array_cell_template(): void
    {
        $html = $this->renderSingleColumn(type: 'array', value: ['foo' => 'bar']);

        self::assertStringContainsString('<code>', $html);
        self::assertStringContainsString('{&quot;foo&quot;:&quot;bar&quot;}', $html);
    }

    public function test_it_falls_back_to_default_template_for_unknown_type(): void
    {
        $html = $this->renderSingleColumn(type: 'unknown', value: 'Fallback');

        self::assertStringContainsString('Fallback', $html);
    }

    public function test_it_renders_a_translated_rich_enum_presentation(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');
        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);
        $translator = $this->addTranslationExtension($twig, 'fr');
        $translator->addResource('array', ['status.active' => 'Actif'], 'fr', 'users');
        $definition = new DatatableDefinition('users');
        $definition
            ->setTranslationDomain('users')
            ->addColumn(
                name: 'status',
                type: 'enum',
                enumClass: RendererStatus::class,
                enumPresentations: [
                    RendererStatus::Active->value => new EnumPresentation(
                        label: 'status.active',
                        badgeVariant: 'success',
                        icon: 'bi bi-check-circle',
                    ),
                ],
            )
        ;
        $renderer = new DatatableRenderer(
            $twig,
            enumPresentationResolver: new DefaultEnumPresentationResolver($translator),
        );

        $html = $renderer->renderBody(
            $definition,
            new DatatableResult(
                rows: [['status' => RendererStatus::Active]],
                totalItems: 1,
            ),
        );

        self::assertStringContainsString('text-bg-success', $html);
        self::assertStringContainsString('bi bi-check-circle', $html);
        self::assertStringContainsString('Actif', $html);
        self::assertStringNotContainsString('status.active', $html);
    }

    public function test_it_uses_a_custom_enum_presentation_resolver(): void
    {
        $resolver = new class implements EnumPresentationResolverInterface {
            public function resolve(
                mixed $value,
                ?string $enumClass = null,
                array $presentations = [],
                ?string $translationDomain = null,
            ): EnumPresentation {
                return new EnumPresentation('Resolved by the application');
            }

            public function resolveChoices(
                string $enumClass,
                array $presentations = [],
                ?string $translationDomain = null,
            ): array {
                return ['Resolved by the application' => 'active'];
            }
        };
        $definition = new DatatableDefinition('users');
        $definition->addColumn(
            name: 'status',
            type: 'enum',
            enumClass: RendererStatus::class,
        );
        $renderer = new DatatableRenderer(
            $this->createTwigEnvironment(),
            enumPresentationResolver: $resolver,
        );

        $html = $renderer->renderBody(
            $definition,
            new DatatableResult(
                rows: [['status' => RendererStatus::Active]],
                totalItems: 1,
            ),
        );

        self::assertStringContainsString('Resolved by the application', $html);
    }

    private function renderSingleColumn(string $type, mixed $value): string
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'value',
            label: 'Value',
            type: $type,
        );

        $result = new DatatableResult(
            rows: [
                [
                    'value' => $value,
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        return $renderer->renderBody($definition, $result);
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

enum RendererStatus: string
{
    case Active = 'active';
}
