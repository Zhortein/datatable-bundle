<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Action;

use Zhortein\DatatableBundle\Definition\ActionDefinition;

final readonly class AllowAllActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition $action, ActionVisibilityContext $context): bool
    {
        return true;
    }
}
