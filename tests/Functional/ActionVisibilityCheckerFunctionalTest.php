<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Zhortein\DatatableBundle\Action\ActionVisibilityCheckerInterface;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Tests\Functional\Kernel\TestKernel;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ActionVisibilityCheckerFunctionalTest extends FunctionalTestCase
{
    public function test_allow_all_action_visibility_checker_is_wired_by_default(): void
    {
        self::bootKernel();

        $checker = self::getContainer()->get('test.'.ActionVisibilityCheckerInterface::class);

        self::assertInstanceOf(AllowAllActionVisibilityChecker::class, $checker);
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
