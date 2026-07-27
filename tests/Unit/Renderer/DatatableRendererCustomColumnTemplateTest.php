<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Cell\CellContextFactory;
use Zhortein\DatatableBundle\Cell\CellValueResolverRegistry;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererCustomColumnTemplateTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_uses_custom_column_template_when_configured(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'status',
            label: 'Status',
            template: '@RendererTest/custom_status_cell.html.twig',
            type: 'boolean',
        );

        $result = new DatatableResult(
            rows: [
                [
                    'status' => true,
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('data-custom-cell="status"', $html);
        self::assertStringContainsString('CUSTOM STATUS: enabled', $html);
        self::assertStringNotContainsString('text-bg-success', $html);
        self::assertStringNotContainsString('Yes', $html);
    }

    public function test_it_passes_column_and_value_to_custom_template(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'email',
            label: 'Email address',
            template: '@RendererTest/custom_debug_cell.html.twig',
            type: 'string',
        );

        $result = new DatatableResult(
            rows: [
                [
                    'email' => 'alice@example.test',
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('Column: email', $html);
        self::assertStringContainsString('Label: Email address', $html);
        self::assertStringContainsString('Value: alice@example.test', $html);
    }

    public function test_it_passes_the_negated_value_to_a_custom_boolean_template(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'status',
            template: '@RendererTest/custom_status_cell.html.twig',
            type: 'boolean',
            negate: true,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());
        $html = $renderer->renderBody(
            $definition,
            new DatatableResult([['status' => true]], 1),
        );

        self::assertStringContainsString('CUSTOM STATUS: disabled', $html);
    }

    public function test_it_does_not_negate_a_non_boolean_custom_column(): void
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'status',
            template: '@RendererTest/custom_status_cell.html.twig',
            type: 'string',
            negate: true,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());
        $html = $renderer->renderBody(
            $definition,
            new DatatableResult([['status' => true]], 1),
        );

        self::assertStringContainsString('CUSTOM STATUS: enabled', $html);
    }

    public function test_it_passes_complete_context_without_serializing_the_source_implicitly(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->setContext(new DatatableContext(['scope' => 'admin']))
            ->addColumn(
                name: 'email',
                label: 'Email',
                template: '@RendererTest/custom_context_cell.html.twig',
            )
        ;
        $source = new \stdClass();
        $source->label = 'Server source';
        $source->secret = 'must-not-leak';
        $result = new DatatableResult(
            rows: [['id' => 7, 'email' => 'alice@example.test']],
            totalItems: 1,
            sources: [$source],
        );

        $html = new DatatableRenderer($this->createTwigEnvironment())->renderBody($definition, $result);

        self::assertStringContainsString('Value: alice@example.test', $html);
        self::assertStringContainsString('Cell value: alice@example.test', $html);
        self::assertStringContainsString('Row: alice@example.test', $html);
        self::assertStringContainsString('Source: Server source', $html);
        self::assertStringContainsString('Identifier: 7', $html);
        self::assertStringContainsString('Datatable: users', $html);
        self::assertStringContainsString('Scope: admin', $html);
        self::assertStringContainsString('Missing: not-allowed', $html);
        self::assertStringNotContainsString('must-not-leak', $html);
        self::assertStringNotContainsString('data-source', $html);
    }

    public function test_it_renders_a_computed_value_from_the_same_cell_context(): void
    {
        $resolver = new class implements CellValueResolverInterface {
            public function getName(): string
            {
                return 'account_summary';
            }

            public function resolve(CellContext $context): mixed
            {
                return sprintf(
                    '%s / %s',
                    strtoupper((string) ($context->getRow()['email'] ?? '')),
                    (string) $context->getDatatableContext()->get('scope'),
                );
            }
        };
        $definition = new DatatableDefinition('users');
        $definition
            ->setContext(new DatatableContext(['scope' => 'admin']))
            ->addComputedColumn(
                name: 'account_summary',
                valueResolver: 'account_summary',
                label: 'Summary',
            )
        ;
        $renderer = new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            cellContextFactory: new CellContextFactory(new CellValueResolverRegistry([$resolver])),
        );

        $html = $renderer->renderBody(
            $definition,
            new DatatableResult([['id' => 1, 'email' => 'alice@example.test']], totalItems: 1),
        );

        self::assertStringContainsString('ALICE@EXAMPLE.TEST / admin', $html);
    }

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');
        $loader->addPath(__DIR__.'/templates', 'RendererTest');

        $twig = new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);

        $this->addTranslationExtension($twig);

        return $twig;
    }
}
