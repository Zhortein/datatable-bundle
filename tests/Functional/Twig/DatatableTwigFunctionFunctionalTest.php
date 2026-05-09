<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Twig;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Twig\Environment;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DatatableTwigFunctionFunctionalTest extends FunctionalTestCase
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
        self::assertStringContainsString('data-zhortein-datatable-fragments-url-value="/_zhortein/datatable/functional-users/fragments"', $html);
        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('Created at', $html);
        self::assertStringContainsString('No data available.', $html);
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
