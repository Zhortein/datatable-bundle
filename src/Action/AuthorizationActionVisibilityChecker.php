<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Action;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;

final readonly class AuthorizationActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ActionVisibilityCheckerInterface $fallbackChecker = new AllowAllActionVisibilityChecker(),
    ) {
    }

    public function isVisible(ActionDefinition|BulkActionDefinition $action, ActionVisibilityContext $context): bool
    {
        $attribute = $action->getAttribute('permission');

        if (null === $attribute || '' === trim($attribute)) {
            return $this->fallbackChecker->isVisible($action, $context);
        }

        return $this->authorizationChecker->isGranted(
            $attribute,
            $context->getRow() ?? $context->getDefinition(),
        );
    }
}
