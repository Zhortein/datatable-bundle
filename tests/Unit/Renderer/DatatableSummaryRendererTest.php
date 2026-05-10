<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Zhortein\DatatableBundle\Renderer\DatatableSummaryRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableSummaryRendererTest extends TestCase
{
    public function test_it_renders_empty_summary(): void
    {
        $renderer = new DatatableSummaryRenderer($this->createTranslator());

        self::assertSame('No result.', $renderer->render(new DatatableResult()));
    }

    public function test_it_renders_single_result_summary(): void
    {
        $renderer = new DatatableSummaryRenderer($this->createTranslator());

        $result = new DatatableResult(
            rows: [['id' => 1]],
            page: 1,
            pageSize: 25,
            totalItems: 1,
        );

        self::assertSame('1 result.', $renderer->render($result));
    }

    public function test_it_renders_multiple_results_summary(): void
    {
        $renderer = new DatatableSummaryRenderer($this->createTranslator());

        $result = new DatatableResult(
            rows: [['id' => 1], ['id' => 2]],
            page: 1,
            pageSize: 2,
            totalItems: 3,
        );

        self::assertSame('Showing 1 to 2 of 3 results.', $renderer->render($result));
    }

    public function test_it_renders_filtered_single_result_summary(): void
    {
        $renderer = new DatatableSummaryRenderer($this->createTranslator());

        $result = new DatatableResult(
            rows: [['id' => 1]],
            page: 1,
            pageSize: 25,
            totalItems: 3,
            filteredItems: 1,
        );

        self::assertSame('1 result found, filtered from 3 total.', $renderer->render($result));
    }

    public function test_it_renders_filtered_multiple_results_summary(): void
    {
        $renderer = new DatatableSummaryRenderer($this->createTranslator());

        $result = new DatatableResult(
            rows: [['id' => 1], ['id' => 2]],
            page: 1,
            pageSize: 2,
            totalItems: 3,
            filteredItems: 2,
        );

        self::assertSame('Showing 1 to 2 of 2 results, filtered from 3 total.', $renderer->render($result));
    }

    private function createTranslator(): Translator
    {
        $translator = new Translator('en');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            __DIR__.'/../../../translations/zhortein_datatable.en.yaml',
            'en',
            'zhortein_datatable',
        );

        return $translator;
    }
}
