<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Action;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Action\AuthorizationActionVisibilityChecker;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final class AuthorizationActionVisibilityCheckerTest extends TestCase
{
    public function test_it_allows_action_when_authorization_checker_grants_permission(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('USER_VIEW', ['e_id' => 42])
            ->willReturn(true)
        ;

        $checker = new AuthorizationActionVisibilityChecker($authorizationChecker);

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            attributes: [
                'permission' => 'USER_VIEW',
            ],
        );

        $context = new ActionVisibilityContext(
            definition: new DatatableDefinition('users'),
            row: ['e_id' => 42],
        );

        self::assertTrue($checker->isVisible($action, $context));
    }

    public function test_it_denies_action_when_authorization_checker_denies_permission(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('USER_DELETE', ['e_id' => 42])
            ->willReturn(false)
        ;

        $checker = new AuthorizationActionVisibilityChecker($authorizationChecker);

        $action = new ActionDefinition(
            name: 'delete',
            route: 'app_user_delete',
            attributes: [
                'permission' => 'USER_DELETE',
            ],
        );

        $context = new ActionVisibilityContext(
            definition: new DatatableDefinition('users'),
            row: ['e_id' => 42],
        );

        self::assertFalse($checker->isVisible($action, $context));
    }

    public function test_it_uses_definition_as_subject_for_global_actions(): void
    {
        $definition = new DatatableDefinition('users');

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('USER_CREATE', $definition)
            ->willReturn(true)
        ;

        $checker = new AuthorizationActionVisibilityChecker($authorizationChecker);

        $action = new ActionDefinition(
            name: 'create',
            route: 'app_user_create',
            attributes: [
                'permission' => 'USER_CREATE',
            ],
        );

        $context = new ActionVisibilityContext(definition: $definition);

        self::assertTrue($checker->isVisible($action, $context));
    }

    public function test_it_uses_fallback_checker_when_no_permission_attribute_is_defined(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->never())
            ->method('isGranted')
        ;

        $checker = new AuthorizationActionVisibilityChecker(
            authorizationChecker: $authorizationChecker,
            fallbackChecker: new AllowAllActionVisibilityChecker(),
        );

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
        );

        self::assertTrue($checker->isVisible(
            $action,
            new ActionVisibilityContext(new DatatableDefinition('users')),
        ));
    }

    public function test_it_uses_fallback_checker_when_permission_attribute_is_empty(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->never())
            ->method('isGranted')
        ;

        $checker = new AuthorizationActionVisibilityChecker($authorizationChecker);

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            attributes: [
                'permission' => ' ',
            ],
        );

        self::assertTrue($checker->isVisible(
            $action,
            new ActionVisibilityContext(new DatatableDefinition('users')),
        ));
    }
}
