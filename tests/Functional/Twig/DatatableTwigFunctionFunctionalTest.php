<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

final class DatatableTwigFunctionFunctionalTest extends KernelTestCase
{
    public function test_it_renders_datatable_through_twig_function(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get(Environment::class);

        self::assertInstanceOf(Environment::class, $twig);

        $html = $twig
            ->createTemplate('{{ zhortein_datatable("functional-users", {search: true}) }}')
            ->render()
        ;

        self::assertStringContainsString('id="zhortein-datatable-functional-users"', $html);
        self::assertStringContainsString('data-controller="zhortein-datatable"', $html);
        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('Created at', $html);
        self::assertStringContainsString('No data available.', $html);
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        restore_exception_handler();
    }
}
