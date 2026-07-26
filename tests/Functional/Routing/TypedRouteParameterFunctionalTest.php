<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Routing;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\RouteParameter;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;
use Zhortein\DatatableBundle\Tests\Functional\FunctionalTestCase;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class TypedRouteParameterFunctionalTest extends FunctionalTestCase
{
    public function test_fragment_rendering_uses_isolated_contexts_with_a_real_localized_route(): void
    {
        self::bootKernel();

        $renderer = self::getContainer()->get('test.'.DatatableRenderer::class);

        self::assertInstanceOf(DatatableRenderer::class, $renderer);

        $frenchDefinition = $this->createDefinition('articles-fr', 'fr');
        $englishDefinition = $this->createDefinition('articles-en', 'en');

        $frenchHtml = $renderer->renderBody(
            $frenchDefinition,
            $this->createResult(42, 'Titre français', 'article français'),
        );
        $englishHtml = $renderer->renderBody(
            $englishDefinition,
            $this->createResult(84, 'English title', 'english article'),
        );

        self::assertStringContainsString('/fr/articles/42/article%20fran%C3%A7ais/preview', $frenchHtml);
        self::assertStringContainsString('/en/articles/84/english%20article/preview', $englishHtml);
        self::assertStringNotContainsString('/en/articles/', $frenchHtml);
        self::assertStringNotContainsString('/fr/articles/', $englishHtml);
    }

    private function createDefinition(string $name, string $locale): DatatableDefinition
    {
        $definition = new DatatableDefinition($name);

        $definition
            ->setContext(new DatatableContext(['locale' => $locale]))
            ->addColumn('frTranslation.title', label: 'Title')
            ->addRowAction(
                name: 'preview',
                route: 'test_article_preview',
                label: 'Preview',
                routeParameters: [
                    '_locale' => RouteParameter::context('locale'),
                    'id' => RouteParameter::row('e.id'),
                    'slug' => RouteParameter::row('translation.slug'),
                ],
            )
        ;

        return $definition;
    }

    private function createResult(int $id, string $title, string $slug): DatatableResult
    {
        return new DatatableResult(
            rows: [[
                'e_id' => $id,
                'frTranslation_title' => $title,
                'translation' => ['slug' => $slug],
            ]],
            page: 1,
            pageSize: 25,
            totalItems: 1,
            filteredItems: 1,
        );
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
