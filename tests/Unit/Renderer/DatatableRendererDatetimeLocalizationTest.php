<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererDatetimeLocalizationTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_datetime_cell_through_datetime_formatter(): void
    {
        $renderer = new DatatableRenderer($this->createTwigEnvironment());

        $html = $renderer->renderBody(
            $this->createDefinition(),
            $this->createResult(),
        );

        self::assertStringContainsString('2026', $html);
        self::assertMatchesRegularExpression('/2:30|14:30/', $html);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('users');

        $definition->addColumn(
            name: 'createdAt',
            label: 'Created at',
            type: 'datetime',
        );

        return $definition;
    }

    private function createResult(): DatatableResult
    {
        return new DatatableResult(
            rows: [
                [
                    'createdAt' => new \DateTimeImmutable('2026-05-09 14:30:00'),
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
