<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Action;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;

/**
 * Shows an action holding a permission only to whom the authorization checker grants it: a row action is decided on
 * its row's source when the provider attached one, such as the entity, otherwise on the row; any other action on the
 * definition.
 */
final readonly class AuthorizationActionVisibilityChecker implements ActionVisibilityCheckerInterface
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ActionVisibilityCheckerInterface $fallbackChecker = new AllowAllActionVisibilityChecker(),
    ) {
    }

    public function isVisible(ActionDefinition|BulkActionDefinition $action, ActionVisibilityContext $context): bool
    {
        $attribute = $action->getPermission();

        if (null === $attribute || '' === trim($attribute)) {
            return $this->fallbackChecker->isVisible($action, $context);
        }

        return $this->authorizationChecker->isGranted(
            $attribute,
            $context->getSource() ?? $context->getRow() ?? $context->getDefinition(),
        );
    }
}
