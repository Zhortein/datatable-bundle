<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Hierarchy\AllowAllChildDatatableAuthorizationChecker;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ChildDatatableAuthorizationCheckerFunctionalTest extends FunctionalTestCase
{
    public function test_allow_all_child_datatable_authorization_checker_is_wired_by_default(): void
    {
        self::bootKernel();

        $checker = self::getContainer()->get('test.'.ChildDatatableAuthorizationCheckerInterface::class);

        self::assertInstanceOf(AllowAllChildDatatableAuthorizationChecker::class, $checker);
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
