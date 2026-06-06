<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Action;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final class AllowAllActionVisibilityCheckerTest extends TestCase
{
    public function test_it_allows_row_actions(): void
    {
        $checker = new AllowAllActionVisibilityChecker();

        self::assertTrue($checker->isVisible(
            new ActionDefinition(name: 'view', route: 'app_user_show'),
            new ActionVisibilityContext(
                definition: new DatatableDefinition('users'),
                row: ['id' => 42],
            ),
        ));
    }

    public function test_it_allows_global_actions(): void
    {
        $checker = new AllowAllActionVisibilityChecker();

        self::assertTrue($checker->isVisible(
            new ActionDefinition(name: 'create', route: 'app_user_create'),
            new ActionVisibilityContext(
                definition: new DatatableDefinition('users'),
            ),
        ));
    }

    public function test_it_allows_bulk_actions(): void
    {
        $checker = new AllowAllActionVisibilityChecker();

        self::assertTrue($checker->isVisible(
            new BulkActionDefinition(name: 'delete', route: 'app_user_bulk_delete'),
            new ActionVisibilityContext(
                definition: new DatatableDefinition('users'),
            ),
        ));
    }
}
