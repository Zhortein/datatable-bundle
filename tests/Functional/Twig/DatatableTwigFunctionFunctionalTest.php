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
        self::assertStringContainsString('data-controller="zhortein--datatable-bundle--datatable"', $html);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-fragments-url-value="/_zhortein/datatable/functional-users/fragments"', $html);
        self::assertStringContainsString('type="search"', $html);
        self::assertStringContainsString('Email', $html);
        self::assertStringContainsString('Created at', $html);
        self::assertStringContainsString('No data available.', $html);
    }

    public function test_it_renders_datatable_with_boolean_display_mode(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        $html = $twig
            ->createTemplate('{{ zhortein_datatable("functional-users", {booleanDisplayMode: "switch"}) }}')
            ->render()
        ;

        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-boolean-display-mode-value="switch"', $html);
    }

    public function test_it_links_to_a_state_under_the_parameter_the_rendered_datatable_reads(): void
    {
        self::bootKernel();
        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        $html = $twig->createTemplate(<<<'TWIG'
            {{ zhortein_datatable("functional-users") }}
            <a id="state-link" href="{{ zhortein_datatable_path(
                "zhortein_datatable_fragments",
                {name: "functional-users"},
                "functional-users",
                {filters: {email: "alice"}}
            ) }}">
            TWIG)->render();
        preg_match('/datatable-state-parameter-value="([^"]+)"/', $html, $parameter);
        preg_match('/id="state-link" href="([^"]+)"/', $html, $href);
        $query = (string) parse_url(html_entity_decode($href[1] ?? ''), \PHP_URL_QUERY);

        self::assertStringStartsWith(rawurlencode(html_entity_decode($parameter[1] ?? '')).'=', $query);
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
