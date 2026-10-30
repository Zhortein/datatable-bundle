<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\State;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\SortDirection;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\State\DatatableState;
use Zhortein\DatatableBundle\State\DatatableStateUrlGenerator;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;
use Zhortein\DatatableBundle\Tests\Unit\Renderer\TranslatableRendererTestTrait;

final class DatatableStateUrlGeneratorTest extends TestCase
{
    use TranslatableRendererTestTrait;

    private const int PAGE_SIZE = 30;

    public function test_it_opens_the_page_in_the_state_given_with_the_default_sort_and_page_size(): void
    {
        $advancedFilters = [
            'logic' => 'and',
            'conditions' => [['field' => 'status', 'operator' => 'neq', 'value' => 'cancelled']],
        ];

        $url = $this->createGenerator()->generate(
            'orders',
            ['year' => 2026],
            'orders',
            filters: ['paid' => '0'],
            advancedFilters: $advancedFilters,
        );

        [$path, $parameter, $state] = $this->readUrl($url);
        self::assertSame('/orders/2026', $path);
        self::assertSame($this->renderedStateParameter([]), $parameter);
        self::assertSame(['paid' => '0'], $state->getFilters());
        self::assertSame($advancedFilters, $state->getAdvancedFilters());
        self::assertEquals(
            [SortCriterion::create('e.status'), SortCriterion::create('e.orderDate', SortDirection::Desc)],
            $state->getSorts(),
        );
        self::assertSame(self::PAGE_SIZE, $state->getPageSize());
    }

    public function test_it_names_the_state_of_a_datatable_rendered_in_a_context_as_the_page_does(): void
    {
        $url = $this->createGenerator()->generate('orders', ['year' => 2026], 'orders', context: ['customer' => 42]);

        self::assertSame($this->renderedStateParameter(['customer' => 42]), $this->readUrl($url)[1]);
        self::assertNotSame($this->renderedStateParameter([]), $this->readUrl($url)[1]);
    }

    /**
     * The path, the state parameter and the state of a generated address.
     *
     * @return array{string, string, DatatableState}
     */
    private function readUrl(string $url): array
    {
        $query = (string) parse_url($url, \PHP_URL_QUERY);
        [$parameter, $payload] = array_map(rawurldecode(...), explode('=', $query, 2));

        $state = new DatatableStateUrlSerializer()->deserialize($payload);

        return [(string) parse_url($url, \PHP_URL_PATH), $parameter, $state];
    }

    /**
     * The state parameter the page reads, as the renderer names it for the datatable rendered in the context given.
     *
     * @param array<string, mixed> $context
     */
    private function renderedStateParameter(array $context): string
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../templates', 'ZhorteinDatatable');
        $twig = new Environment($loader, ['strict_variables' => true, 'autoescape' => 'html']);
        $this->addTranslationExtension($twig);
        $renderer = new DatatableRenderer(
            twig: $twig,
            contextTransport: $this->createContextTransport(),
            stateUrlSerializer: new DatatableStateUrlSerializer(),
        );

        $html = $renderer->render($this->createDefinitionFactory()->create('orders'), ['context' => $context]);
        preg_match('/data-zhortein--datatable-bundle--datatable-state-parameter-value="([^"]+)"/', $html, $match);

        return html_entity_decode($match[1] ?? '');
    }

    private function createGenerator(): DatatableStateUrlGenerator
    {
        $routes = new RouteCollection();
        $routes->add('orders', new Route('/orders/{year}'));

        return new DatatableStateUrlGenerator(
            definitionFactory: $this->createDefinitionFactory(),
            contextTransport: $this->createContextTransport(),
            serializer: new DatatableStateUrlSerializer(),
            urlGenerator: new UrlGenerator($routes, new RequestContext()),
            defaultPageSize: self::PAGE_SIZE,
        );
    }

    private function createDefinitionFactory(): DatatableDefinitionFactory
    {
        return new DatatableDefinitionFactory(new DatatableRegistry(
            new ServiceLocator(['orders' => static fn (): StateUrlTestDatatable => new StateUrlTestDatatable()]),
            ['orders' => StateUrlTestDatatable::class],
        ));
    }

    private function createContextTransport(): DatatableContextTransport
    {
        return new DatatableContextTransport('state-url-test-secret');
    }
}

final class StateUrlTestDatatable implements DatatableInterface
{
    public function buildDatatable(DatatableDefinition $definition): void
    {
        $definition
            ->setEntityClass(\stdClass::class)
            ->setContext(new DatatableContext(['customer' => 0], browserSafeKeys: ['customer']))
            ->setDefaultSorts(
                SortCriterion::create('e.status'),
                SortCriterion::create('e.orderDate', SortDirection::Desc),
            )
            ->addColumn('e.status', label: 'Status')
            ->addColumn('e.orderDate', label: 'Order date')
        ;
    }
}
