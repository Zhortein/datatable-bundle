<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Preference;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Preference\NullDatatablePreferenceProvider;

final class NullDatatablePreferenceProviderTest extends TestCase
{
    public function test_it_returns_empty_preference(): void
    {
        $provider = new NullDatatablePreferenceProvider();

        $preference = $provider->getPreference('users');

        self::assertTrue($preference->isEmpty());
        self::assertSame([], $preference->toRenderOptions());
    }
}
