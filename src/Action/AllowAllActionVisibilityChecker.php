<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Action;

use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;

final readonly class AllowAllActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition|BulkActionDefinition $action, ActionVisibilityContext $context): bool
    {
        return true;
    }
}
