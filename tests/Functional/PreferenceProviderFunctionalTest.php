<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Preference\NullDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PreferenceProviderFunctionalTest extends FunctionalTestCase
{
    public function test_null_preference_provider_is_wired_by_default(): void
    {
        self::bootKernel();

        $provider = self::getContainer()->get('test.'.DatatablePreferenceProviderInterface::class);

        self::assertInstanceOf(NullDatatablePreferenceProvider::class, $provider);
        self::assertTrue($provider->getPreference('users')->isEmpty());
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
