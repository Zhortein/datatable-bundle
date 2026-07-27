<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Hierarchy\AllowAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableContextResolver;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableInstanceFactory;
use Zhortein\DatatableBundle\Hierarchy\ChildDatatableResolver;
use Zhortein\DatatableBundle\Hierarchy\DenyAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Hierarchy\RowValueAccessor;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\Result\DatatableResult;

final class DatatableRendererChildDatatableTest extends TestCase
{
    use TranslatableRendererTestTrait;

    public function test_it_renders_accessible_signed_child_rows(): void
    {
        $transport = new DatatableContextTransport('renderer-test-secret');
        $renderer = $this->createRenderer(
            $transport,
            new AllowAllChildDatatableAuthorizationChecker(),
        );
        $definition = $this->createParentDefinition();
        $body = $renderer->renderBody(
            $definition,
            new DatatableResult(
                rows: [
                    ['id' => 42, 'name' => 'First order'],
                    ['id' => 43, 'name' => 'Second order'],
                ],
                totalItems: 2,
            ),
            ['instance' => 'active-orders'],
        );
        $header = $renderer->renderHeader($definition, ['instance' => 'active-orders']);

        self::assertSame(2, substr_count($body, 'data-zhortein--datatable-bundle--datatable-child-toggle="true"'));
        self::assertSame(2, substr_count($body, 'data-zhortein--datatable-bundle--datatable-child-row="true"'));
        self::assertStringContainsString('aria-expanded="false"', $body);
        self::assertStringContainsString('aria-label="Expand row 42"', $body);
        self::assertStringContainsString('data-zhortein--datatable-bundle--datatable-child-collapse-label="Collapse row 42"', $body);
        self::assertStringContainsString('class="zhortein-datatable__child-row"', $body);
        self::assertStringContainsString('colspan="2"', $body);
        self::assertStringContainsString('Child rows', $header);

        $childUrl = $this->extractAttribute(
            $body,
            'data-zhortein--datatable-bundle--datatable-child-url',
        );
        $parameters = $this->parseQuery($childUrl);
        $instance = $parameters[DatatableContextTransport::INSTANCE_QUERY_PARAMETER];
        $token = $parameters[DatatableContextTransport::CONTEXT_QUERY_PARAMETER];

        self::assertStringStartsWith('/_zhortein/datatable/order-lines/child?', $childUrl);
        self::assertMatchesRegularExpression('/^zd-child-d1-[A-Za-z0-9_-]{43}$/D', $instance);

        $restored = $transport->restore(
            $token,
            'order-lines',
            $instance,
            new DatatableContext(['orderId' => null], ['orderId']),
        );

        self::assertSame(42, $restored->get('orderId'));
    }

    public function test_it_hides_a_child_rejected_by_the_authorization_checker(): void
    {
        $renderer = $this->createRenderer(
            new DatatableContextTransport('renderer-test-secret'),
            new DenyAllChildDatatableAuthorizationChecker(),
        );
        $body = $renderer->renderBody(
            $this->createParentDefinition(),
            new DatatableResult(
                rows: [['id' => 42, 'name' => 'First order']],
                totalItems: 1,
            ),
        );

        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-child-toggle="true"', $body);
        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-child-row="true"', $body);
    }

    public function test_it_requires_an_identifier_for_every_expandable_row(): void
    {
        $renderer = $this->createRenderer(
            new DatatableContextTransport('renderer-test-secret'),
            new AllowAllChildDatatableAuthorizationChecker(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A child datatable parent row identifier must be a non-empty scalar');

        $renderer->renderBody(
            $this->createParentDefinition(),
            new DatatableResult(
                rows: [['name' => 'Missing identifier']],
                totalItems: 1,
            ),
        );
    }

    public function test_it_omits_expand_controls_at_the_maximum_depth(): void
    {
        $renderer = $this->createRenderer(
            new DatatableContextTransport('renderer-test-secret'),
            new AllowAllChildDatatableAuthorizationChecker(),
        );
        $definition = $this->createParentDefinition();
        $options = ['childDepth' => 3];
        $body = $renderer->renderBody(
            $definition,
            new DatatableResult(
                rows: [['id' => 42, 'name' => 'Maximum depth']],
                totalItems: 1,
            ),
            $options,
        );
        $header = $renderer->renderHeader($definition, $options);

        self::assertStringNotContainsString('data-zhortein--datatable-bundle--datatable-child-toggle="true"', $body);
        self::assertStringNotContainsString('zhortein-datatable__child-toggle-column', $header);
    }

    private function createParentDefinition(): DatatableDefinition
    {
        return new DatatableDefinition('orders')
            ->addColumn('name', label: 'Order')
            ->setChildDatatable('order-lines', [
                'orderId' => ChildContextValue::row('id'),
            ])
        ;
    }

    private function createRenderer(
        DatatableContextTransport $transport,
        ChildDatatableAuthorizationCheckerInterface $authorizationChecker,
    ): DatatableRenderer {
        return new DatatableRenderer(
            twig: $this->createTwigEnvironment(),
            urlGenerator: new ChildDatatableUrlGeneratorFixture(),
            contextTransport: $transport,
            childDatatableResolver: new ChildDatatableResolver(
                new ChildDatatableContextResolver(new RowValueAccessor()),
                new ChildDatatableInstanceFactory(),
                $transport,
                $authorizationChecker,
            ),
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
        $matched = preg_match(
            sprintf('/%s="([^"]+)"/', preg_quote($attribute, '/')),
            $html,
            $matches,
        );

        self::assertSame(1, $matched);

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
    }

    /**
     * @return array<string, string>
     */
    private function parseQuery(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);

        self::assertIsString($query);
        parse_str($query, $parameters);

        /** @var array<string, string> $parameters */
        return $parameters;
    }
}

final class ChildDatatableUrlGeneratorFixture implements UrlGeneratorInterface
{
    /**
     * @param array<mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = self::ABSOLUTE_PATH,
    ): string {
        if ('zhortein_datatable_child' !== $name) {
            throw new \InvalidArgumentException(sprintf('Unexpected route "%s".', $name));
        }

        $childName = $parameters['name'] ?? null;

        if (!is_string($childName)) {
            throw new \InvalidArgumentException('The child datatable route requires a name.');
        }

        return sprintf('/_zhortein/datatable/%s/child', rawurlencode($childName));
    }

    public function setContext(RequestContext $context): void
    {
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }
}
