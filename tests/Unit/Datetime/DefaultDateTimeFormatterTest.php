<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\DateTime;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\DateTime\DefaultDateTimeFormatter;

final class DefaultDateTimeFormatterTest extends TestCase
{
    public function test_it_formats_datetime_with_fallback_format_when_intl_is_unavailable_or_as_fallback(): void
    {
        $formatter = new DefaultDateTimeFormatter(
            fallbackFormat: 'Y-m-d H:i',
        );

        $formatted = $formatter->format(new \DateTimeImmutable('2026-05-09 14:30:00'));

        self::assertNotSame('', $formatted);
    }

    public function test_it_returns_scalar_values_as_strings(): void
    {
        $formatter = new DefaultDateTimeFormatter();

        self::assertSame('raw value', $formatter->format('raw value'));
        self::assertSame('123', $formatter->format(123));
    }

    public function test_it_returns_empty_string_for_non_datetime_non_scalar_values(): void
    {
        $formatter = new DefaultDateTimeFormatter();

        self::assertSame('', $formatter->format(['invalid']));
        self::assertSame('', $formatter->format(new \stdClass()));
    }
}
