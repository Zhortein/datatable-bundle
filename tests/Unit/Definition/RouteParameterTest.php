<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\RouteParameter;
use Zhortein\DatatableBundle\Enum\RouteParameterSource;

final class RouteParameterTest extends TestCase
{
    public function test_it_describes_required_sources(): void
    {
        $row = RouteParameter::row(' e.id ');
        $context = RouteParameter::context('locale');
        $literal = RouteParameter::literal('preview');

        self::assertSame(RouteParameterSource::Row, $row->getSource());
        self::assertSame('e.id', $row->getKey());
        self::assertTrue($row->isRequired());
        self::assertFalse($row->hasDefault());

        self::assertSame(RouteParameterSource::Context, $context->getSource());
        self::assertSame('locale', $context->getKey());

        self::assertSame(RouteParameterSource::Literal, $literal->getSource());
        self::assertNull($literal->getKey());
        self::assertSame('preview', $literal->getValue());
    }

    public function test_it_describes_optional_and_defaulted_sources(): void
    {
        $optional = RouteParameter::optionalRow('slug');
        $defaulted = RouteParameter::contextOr('locale', 'en');

        self::assertFalse($optional->isRequired());
        self::assertFalse($optional->hasDefault());
        self::assertFalse($defaulted->isRequired());
        self::assertTrue($defaulted->hasDefault());
        self::assertSame('en', $defaulted->getDefaultValue());
    }

    public function test_it_rejects_empty_referenced_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A row route parameter source key must not be empty.');

        RouteParameter::row(' ');
    }
}
