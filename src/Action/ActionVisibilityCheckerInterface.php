<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Action;

use Zhortein\DatatableBundle\Definition\ActionDefinition;

interface ActionVisibilityCheckerInterface
{
    public function isVisible(ActionDefinition $action, ActionVisibilityContext $context): bool;
}
