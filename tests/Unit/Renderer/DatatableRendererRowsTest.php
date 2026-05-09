<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererRowsTest extends TestCase
{
    public function test_it_renders_rows_from_datatable_result(): void
    {
        $definition = $this->createDefinition();
        $result = new DatatableResult(
            rows: [
                [
                    'id' => 1,
                    'email' => 'alice@example.test',
                    'displayName' => 'Alice',
                ],
                [
                    'id' => 2,
                    'email' => 'bob@example.test',
                    'displayName' => 'Bob',
                ],
            ],
            totalItems: 2,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('<tr>', $html);
        self::assertStringContainsString('alice@example.test', $html);
        self::assertStringContainsString('Alice', $html);
        self::assertStringContainsString('bob@example.test', $html);
        self::assertStringContainsString('Bob', $html);
        self::assertStringNotContainsString('<td>1</td>', $html);
        self::assertStringContainsString('class="text-start"', $html);
    }

    public function test_it_renders_empty_state_when_result_has_no_rows(): void
    {
        $definition = $this->createDefinition();
        $result = new DatatableResult();

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('No data available.', $html);
        self::assertStringContainsString('colspan="2"', $html);
    }

    public function test_it_supports_rows_keyed_by_full_column_name(): void
    {
        $definition = $this->createDefinition();
        $result = new DatatableResult(
            rows: [
                [
                    'e.email' => 'full@example.test',
                    'e.displayName' => 'Full Name',
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringContainsString('full@example.test', $html);
        self::assertStringContainsString('Full Name', $html);
    }

    public function test_it_escapes_cell_values(): void
    {
        $definition = $this->createDefinition();
        $result = new DatatableResult(
            rows: [
                [
                    'email' => '<script>alert("xss")</script>',
                    'displayName' => 'Alice',
                ],
            ],
            totalItems: 1,
        );

        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody($definition, $result);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email', label: 'Email', className: 'text-start')
            ->addColumn('e.displayName', label: 'Display name')
        ;

        return $definition;
    }

    private function createTwigEnvironment(): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');

        return new Environment($loader, [
            'strict_variables' => true,
            'autoescape' => 'html',
        ]);
    }
}
