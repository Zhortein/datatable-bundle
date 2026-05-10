<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Attribute\AsTwigFunction;
use Zhortein\DatatableBundle\DateTime\DefaultDateTimeFormatter;
use Zhortein\DatatableBundle\Twig\DateTimeTwigExtension;

final class DateTimeTwigExtensionTest extends TestCase
{
    public function test_format_method_is_exposed_as_twig_function(): void
    {
        $reflectionMethod = new \ReflectionMethod(DateTimeTwigExtension::class, 'formatDateTime');
        $attributes = $reflectionMethod->getAttributes(AsTwigFunction::class);

        self::assertCount(1, $attributes);

        $attribute = $attributes[0]->newInstance();

        self::assertSame('zhortein_datatable_datetime', $attribute->name);
    }

    public function test_it_formats_datetime_values(): void
    {
        $extension = new DateTimeTwigExtension(new DefaultDateTimeFormatter(
            fallbackFormat: 'Y-m-d H:i',
        ));

        $formatted = $extension->formatDateTime(new \DateTimeImmutable('2026-05-09 14:30:00'));

        self::assertNotSame('', $formatted);
    }
}
