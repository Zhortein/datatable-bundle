<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Definition\AjaxActionOptions;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\RouteParameter;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererContextTransportTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_propagates_signed_context_to_fragments_exports_and_ajax_actions(): void
    {
        $transport = new DatatableContextTransport('renderer-test-secret');
        $definition = $this->createDefinition();
        $renderer = $this->createRenderer($transport);
        $options = [
            'instance' => 'french-table',
            'context' => [
                'locale' => 'fr',
                'tenant' => 'acme',
            ],
            'exportFormats' => ['csv', 'xlsx'],
        ];

        $shell = $renderer->render($definition, $options);
        $body = $renderer->renderBody(
            $definition,
            new DatatableResult(
                rows: [['e_id' => 42, 'title' => 'Signed context']],
                totalItems: 1,
                filteredItems: 1,
            ),
            ['instance' => 'french-table'],
        );

        self::assertStringContainsString('id="zhortein-datatable-articles-french-table"', $shell);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-instance-value="french-table"', $shell);

        $fragmentsUrl = $this->extractAttribute($shell, 'data-zhortein--datatable-bundle--datatable-fragments-url-value');
        $exportUrl = $this->extractAttribute($shell, 'data-zhortein--datatable-bundle--datatable-export-url-value');
        $fragmentsQuery = $this->parseQuery($fragmentsUrl);
        $exportQuery = $this->parseQuery($exportUrl);

        self::assertSame('french-table', $fragmentsQuery[DatatableContextTransport::INSTANCE_QUERY_PARAMETER]);
        self::assertSame(
            $fragmentsQuery[DatatableContextTransport::CONTEXT_QUERY_PARAMETER],
            $exportQuery[DatatableContextTransport::CONTEXT_QUERY_PARAMETER],
        );
        self::assertMatchesRegularExpression('/_zd_context=[^"&]+&mode=current/', html_entity_decode($shell));
        self::assertMatchesRegularExpression('/_zd_context=[^"&]+&mode=full/', html_entity_decode($shell));
        self::assertStringContainsString('_zd_instance=french-table', html_entity_decode($body));
        self::assertStringContainsString('_zd_context=', html_entity_decode($body));
        self::assertStringContainsString('/app_article_preview?id=42&amp;locale=fr', $body);

        $restored = $transport->restore(
            $fragmentsQuery[DatatableContextTransport::CONTEXT_QUERY_PARAMETER],
            'articles',
            'french-table',
            new DatatableContext(['locale' => 'en', 'tenant' => 'default'], ['locale', 'tenant']),
        );

        self::assertSame('fr', $restored->get('locale'));
        self::assertSame('acme', $restored->get('tenant'));
    }

    public function test_two_instances_of_the_same_datatable_remain_isolated(): void
    {
        $transport = new DatatableContextTransport('renderer-test-secret');
        $renderer = $this->createRenderer($transport);

        $frenchHtml = $renderer->render($this->createDefinition(), [
            'instance' => 'french-table',
            'context' => ['locale' => 'fr', 'tenant' => 'acme'],
        ]);
        $englishHtml = $renderer->render($this->createDefinition(), [
            'instance' => 'english-table',
            'context' => ['locale' => 'en', 'tenant' => 'isatis'],
        ]);

        $frenchQuery = $this->parseQuery($this->extractAttribute(
            $frenchHtml,
            'data-zhortein--datatable-bundle--datatable-fragments-url-value',
        ));
        $englishQuery = $this->parseQuery($this->extractAttribute(
            $englishHtml,
            'data-zhortein--datatable-bundle--datatable-fragments-url-value',
        ));

        self::assertStringContainsString('id="zhortein-datatable-articles-french-table"', $frenchHtml);
        self::assertStringContainsString('id="zhortein-datatable-articles-english-table"', $englishHtml);
        self::assertNotSame(
            $frenchQuery[DatatableContextTransport::CONTEXT_QUERY_PARAMETER],
            $englishQuery[DatatableContextTransport::CONTEXT_QUERY_PARAMETER],
        );
    }

    public function test_a_render_cannot_override_a_server_only_context_key(): void
    {
        $renderer = $this->createRenderer(new DatatableContextTransport('renderer-test-secret'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The datatable context key "server" is not allowlisted for browser propagation.');

        $renderer->render($this->createDefinition(), [
            'context' => ['server' => 'exposed'],
        ]);
    }

    private function createDefinition(): DatatableDefinition
    {
        $definition = new DatatableDefinition('articles');
        $definition
            ->setContext(new DatatableContext([
                'locale' => 'en',
                'tenant' => 'default',
                'server' => new \stdClass(),
            ], ['locale', 'tenant']))
            ->addColumn('title', label: 'Title')
            ->addRowAction(
                name: 'preview',
                route: 'app_article_preview',
                routeParameters: [
                    'id' => RouteParameter::row('e.id'),
                    'locale' => RouteParameter::context('locale'),
                ],
                ajax: new AjaxActionOptions(),
            )
        ;

        return $definition;
    }

    private function createRenderer(DatatableContextTransport $transport): DatatableRenderer
    {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new ContextTransportUrlGeneratorFixture(),
            routeParameterResolver: new RowActionRouteParameterResolver(),
            contextTransport: $transport,
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

    private function extractAttribute(string $html, string $attribute): string
    {
        $matched = preg_match(sprintf('/%s="([^"]+)"/', preg_quote($attribute, '/')), $html, $matches);

        self::assertSame(1, $matched);

        return html_entity_decode($matches[1]);
    }

    /**
     * @return array<string, string>
     */
    private function parseQuery(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);

        self::assertIsString($query);
        parse_str($query, $parameters);
        self::assertIsArray($parameters);

        /** @var array<string, string> $parameters */
        return $parameters;
    }
}

final class ContextTransportUrlGeneratorFixture implements UrlGeneratorInterface
{
    /**
     * @param array<mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string {
        $query = http_build_query($parameters, arg_separator: '&', encoding_type: PHP_QUERY_RFC3986);

        return '/'.$name.('' === $query ? '' : '?'.$query);
    }

    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }
}
