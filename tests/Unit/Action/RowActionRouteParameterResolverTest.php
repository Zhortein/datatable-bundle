<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Action;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Action\RowActionRouteParameterResolver;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;
use Zhortein\DatatableBundle\Definition\RouteParameter;
use Zhortein\DatatableBundle\Exception\InvalidRouteParameterValueException;
use Zhortein\DatatableBundle\Exception\MissingRouteParameterValueException;

final class RowActionRouteParameterResolverTest extends TestCase
{
    public function test_it_resolves_direct_row_keys(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'id',
            ],
        );

        self::assertSame([
            'id' => 42,
        ], $resolver->resolve($action, [
            'id' => 42,
        ]));
    }

    public function test_it_resolves_aliased_row_keys(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'e_id',
            ],
        );

        self::assertSame([
            'id' => 42,
        ], $resolver->resolve($action, [
            'e_id' => 42,
        ]));
    }

    public function test_it_resolves_doctrine_dot_notation_from_aliased_row_key(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'e.id',
            ],
        );

        self::assertSame([
            'id' => 42,
        ], $resolver->resolve($action, [
            'e_id' => 42,
        ]));
    }

    public function test_it_resolves_doctrine_dot_notation_from_direct_last_segment(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'e.id',
            ],
        );

        self::assertSame([
            'id' => 42,
        ], $resolver->resolve($action, [
            'id' => 42,
        ]));
    }

    public function test_it_resolves_multiple_parameters(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_order_line_show',
            routeParameters: [
                'orderId' => 'order_id',
                'lineId' => 'line_id',
            ],
        );

        self::assertSame([
            'orderId' => 10,
            'lineId' => 20,
        ], $resolver->resolve($action, [
            'order_id' => 10,
            'line_id' => 20,
        ]));
    }

    public function test_it_returns_empty_parameters_when_action_has_no_route_parameters(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'create',
            route: 'app_user_create',
        );

        self::assertSame([], $resolver->resolve($action, [
            'id' => 42,
        ]));
    }

    public function test_it_throws_when_row_value_is_missing(): void
    {
        $resolver = new RowActionRouteParameterResolver();

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            routeParameters: [
                'id' => 'e.id',
            ],
        );

        $this->expectException(MissingRouteParameterValueException::class);
        $this->expectExceptionMessage('Unable to resolve route parameter "id" for row action "view" from row key "e.id".');

        $resolver->resolve($action, [
            'email' => 'alice@example.test',
        ]);
    }

    public function test_it_reproduces_the_missing_navias_guide_translation_locale(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $action = new ActionDefinition(
            name: 'preview',
            route: 'app_article_preview',
            routeParameters: [
                'locale' => 'frTranslation.locale',
            ],
        );

        $this->expectException(MissingRouteParameterValueException::class);
        $this->expectExceptionMessage('Unable to resolve route parameter "locale" for row action "preview" from row key "frTranslation.locale".');

        $resolver->resolve($action, [
            'e_id' => 42,
            'frTranslation_title' => 'Titre français',
        ]);
    }

    public function test_it_resolves_explicit_row_literal_and_context_sources(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $action = new ActionDefinition(
            name: 'preview',
            route: 'app_article_preview',
            routeParameters: [
                'id' => RouteParameter::row('e.id'),
                'locale' => RouteParameter::context('locale'),
                'format' => RouteParameter::literal('html'),
            ],
        );

        self::assertSame([
            'id' => 42,
            'locale' => 'fr',
            'format' => 'html',
        ], $resolver->resolve(
            $action,
            [
                'e_id' => 42,
                'frTranslation_title' => 'Titre français',
            ],
            new DatatableContext(['locale' => 'fr']),
        ));
    }

    public function test_it_resolves_nested_array_and_object_property_paths(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $action = new ActionDefinition(
            name: 'preview',
            route: 'app_article_preview',
            routeParameters: [
                'arrayLocale' => RouteParameter::row('translation.locale'),
                'objectLocale' => RouteParameter::row('metadata.locale'),
            ],
        );

        self::assertSame([
            'arrayLocale' => 'fr',
            'objectLocale' => 'en',
        ], $resolver->resolve($action, [
            'translation' => ['locale' => 'fr'],
            'metadata' => new RouteParameterMetadataFixture('en'),
        ]));
    }

    public function test_it_applies_required_optional_and_defaulted_null_semantics(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $action = new ActionDefinition(
            name: 'preview',
            route: 'app_article_preview',
            routeParameters: [
                'optionalRow' => RouteParameter::optionalRow('missing'),
                'optionalContext' => RouteParameter::optionalContext('nullable'),
                'defaultedRow' => RouteParameter::rowOr('missing', 'fallback'),
                'defaultedContext' => RouteParameter::contextOr('nullable', 'en'),
                'omittedNullDefault' => RouteParameter::contextOr('missing', null),
            ],
        );

        self::assertSame([
            'defaultedRow' => 'fallback',
            'defaultedContext' => 'en',
        ], $resolver->resolve(
            $action,
            [],
            new DatatableContext(['nullable' => null]),
        ));
    }

    public function test_it_rejects_a_required_null_row_value(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $action = new ActionDefinition(
            name: 'preview',
            route: 'app_article_preview',
            routeParameters: [
                'locale' => RouteParameter::row('locale'),
            ],
        );

        $this->expectException(MissingRouteParameterValueException::class);
        $this->expectExceptionMessage('Unable to resolve required route parameter "locale" for row action "preview" from row source "locale": the row value is null.');

        $resolver->resolve($action, ['locale' => null]);
    }

    public function test_it_rejects_a_context_key_that_was_not_allowlisted(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $action = new ActionDefinition(
            name: 'preview',
            route: 'app_article_preview',
            routeParameters: [
                'locale' => RouteParameter::context('locale'),
            ],
        );

        $this->expectException(MissingRouteParameterValueException::class);
        $this->expectExceptionMessage('Unable to resolve required route parameter "locale" for row action "preview" from context source "locale": the context key is not allowlisted.');

        $resolver->resolve($action, [], new DatatableContext(['tenant' => 'acme']));
    }

    public function test_it_normalizes_backed_enums_and_stringable_values(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $action = new ActionDefinition(
            name: 'preview',
            route: 'app_article_preview',
            routeParameters: [
                'status' => RouteParameter::literal(RouteParameterStatusFixture::Published),
                'identifier' => RouteParameter::literal(new StringableRouteParameterFixture('article-42')),
                'page' => RouteParameter::literal(2),
            ],
        );

        self::assertSame([
            'status' => 'published',
            'identifier' => 'article-42',
            'page' => 2,
        ], $resolver->resolve($action, []));
    }

    public function test_it_rejects_values_that_are_not_supported_by_the_route_contract(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $action = new ActionDefinition(
            name: 'preview',
            route: 'app_article_preview',
            routeParameters: [
                'locale' => RouteParameter::literal(['fr']),
            ],
        );

        $this->expectException(InvalidRouteParameterValueException::class);
        $this->expectExceptionMessage('Unable to resolve route parameter "locale" for row action "preview" from literal source: values of type "array" are not supported by the action route contract.');

        $resolver->resolve($action, []);
    }

    public function test_it_keeps_global_and_bulk_legacy_literals_and_resolves_typed_context(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $context = new DatatableContext(['locale' => 'fr']);
        $globalAction = new ActionDefinition(
            name: 'create',
            route: 'app_article_create',
            routeParameters: [
                'legacy' => 'literal',
                'locale' => RouteParameter::context('locale'),
            ],
        );
        $bulkAction = new BulkActionDefinition(
            name: 'publish',
            route: 'app_article_publish',
            routeParameters: [
                'legacy' => 'literal',
                'locale' => RouteParameter::context('locale'),
            ],
        );

        self::assertSame([
            'legacy' => 'literal',
            'locale' => 'fr',
        ], $resolver->resolveGlobalAction($globalAction, $context));
        self::assertSame([
            'legacy' => 'literal',
            'locale' => 'fr',
        ], $resolver->resolveBulkAction($bulkAction, $context));
    }

    public function test_it_keeps_context_values_isolated_between_datatables(): void
    {
        $resolver = new RowActionRouteParameterResolver();
        $action = new ActionDefinition(
            name: 'preview',
            route: 'app_article_preview',
            routeParameters: [
                'locale' => RouteParameter::context('locale'),
            ],
        );

        self::assertSame(
            ['locale' => 'fr'],
            $resolver->resolve($action, [], new DatatableContext(['locale' => 'fr'])),
        );
        self::assertSame(
            ['locale' => 'en'],
            $resolver->resolve($action, [], new DatatableContext(['locale' => 'en'])),
        );
    }
}

final readonly class RouteParameterMetadataFixture
{
    public function __construct(
        private string $locale,
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}

enum RouteParameterStatusFixture: string
{
    case Published = 'published';
}

final readonly class StringableRouteParameterFixture implements \Stringable
{
    public function __construct(
        private string $value,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
