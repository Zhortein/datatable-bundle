<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Controller\DatatableController;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final class DatatableControllerTest extends TestCase
{
    public function test_it_returns_placeholder_fragments(): void
    {
        $controller = new DatatableController(
            registry: $this->createRegistry(),
            renderer: new DatatableRenderer($this->createTwigEnvironment()),
        );

        $response = $controller->fragments('users');

        self::assertSame(200, $response->getStatusCode());

        $content = $response->getContent();
        self::assertIsString($content);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('body', $payload);
        self::assertArrayHasKey('pagination', $payload);
        self::assertArrayHasKey('summary', $payload);
        self::assertSame(1, $payload['page']);
        self::assertSame(0, $payload['pageSize']);
        self::assertSame(0, $payload['totalItems']);
        self::assertSame(0, $payload['totalPages']);
        self::assertIsString($payload['body']);
        self::assertStringContainsString('No data available.', $payload['body']);
        self::assertStringContainsString('colspan="1"', $payload['body']);
    }

    private function createRegistry(): DatatableRegistry
    {
        $datatable = new ControllerTestDatatable();

        return new DatatableRegistry(
            new ServiceLocator([
                'users' => static fn (): ControllerTestDatatable => $datatable,
            ]),
            ['users' => ControllerTestDatatable::class],
        );
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

final class ControllerTestDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition->addColumn('e.email', label: 'Email');
    }
}
