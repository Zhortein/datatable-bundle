<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Action;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Action\ActionVisibilityContext;
use Zhortein\DatatableBundle\Action\AllowAllActionVisibilityChecker;
use Zhortein\DatatableBundle\Action\AuthorizationActionVisibilityChecker;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final class AuthorizationActionVisibilityCheckerTest extends TestCase
{
    public function test_it_allows_action_when_authorization_checker_grants_permission(): void
    {
        $authorizationChecker = new RecordingAuthorizationChecker([
            'USER_VIEW' => true,
        ]);

        $checker = new AuthorizationActionVisibilityChecker($authorizationChecker);

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            permission: 'USER_VIEW',
        );

        $context = new ActionVisibilityContext(
            definition: new DatatableDefinition('users'),
            row: ['e_id' => 42],
        );

        self::assertTrue($checker->isVisible($action, $context));
        self::assertSame(1, $authorizationChecker->getCallCount());
        self::assertSame('USER_VIEW', $authorizationChecker->getLastAttribute());
        self::assertSame(['e_id' => 42], $authorizationChecker->getLastSubject());
    }

    public function test_it_denies_action_when_authorization_checker_denies_permission(): void
    {
        $authorizationChecker = new RecordingAuthorizationChecker([
            'USER_DELETE' => false,
        ]);

        $checker = new AuthorizationActionVisibilityChecker($authorizationChecker);

        $action = new ActionDefinition(
            name: 'delete',
            route: 'app_user_delete',
            permission: 'USER_DELETE',
        );

        $context = new ActionVisibilityContext(
            definition: new DatatableDefinition('users'),
            row: ['e_id' => 42],
        );

        self::assertFalse($checker->isVisible($action, $context));
        self::assertSame(1, $authorizationChecker->getCallCount());
        self::assertSame('USER_DELETE', $authorizationChecker->getLastAttribute());
        self::assertSame(['e_id' => 42], $authorizationChecker->getLastSubject());
    }

    public function test_it_uses_definition_as_subject_for_global_actions(): void
    {
        $definition = new DatatableDefinition('users');

        $authorizationChecker = new RecordingAuthorizationChecker([
            'USER_CREATE' => true,
        ]);

        $checker = new AuthorizationActionVisibilityChecker($authorizationChecker);

        $action = new ActionDefinition(
            name: 'create',
            route: 'app_user_create',
            permission: 'USER_CREATE',
        );

        $context = new ActionVisibilityContext(definition: $definition);

        self::assertTrue($checker->isVisible($action, $context));
        self::assertSame(1, $authorizationChecker->getCallCount());
        self::assertSame('USER_CREATE', $authorizationChecker->getLastAttribute());
        self::assertSame($definition, $authorizationChecker->getLastSubject());
    }

    public function test_it_uses_definition_as_subject_for_bulk_actions(): void
    {
        $definition = new DatatableDefinition('users');

        $authorizationChecker = new RecordingAuthorizationChecker([
            'USER_BULK_DELETE' => true,
        ]);

        $checker = new AuthorizationActionVisibilityChecker($authorizationChecker);

        $action = new BulkActionDefinition(
            name: 'bulk_delete',
            route: 'app_user_bulk_delete',
            permission: 'USER_BULK_DELETE',
        );

        $context = new ActionVisibilityContext(definition: $definition);

        self::assertTrue($checker->isVisible($action, $context));
        self::assertSame(1, $authorizationChecker->getCallCount());
        self::assertSame('USER_BULK_DELETE', $authorizationChecker->getLastAttribute());
        self::assertSame($definition, $authorizationChecker->getLastSubject());
    }

    public function test_it_uses_fallback_checker_when_no_permission_is_defined(): void
    {
        $authorizationChecker = new RecordingAuthorizationChecker();

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

        self::assertSame(0, $authorizationChecker->getCallCount());
    }

    public function test_it_uses_fallback_checker_when_permission_is_empty(): void
    {
        $authorizationChecker = new RecordingAuthorizationChecker();

        $checker = new AuthorizationActionVisibilityChecker($authorizationChecker);

        $action = new ActionDefinition(
            name: 'view',
            route: 'app_user_show',
            permission: ' ',
        );

        self::assertTrue($checker->isVisible(
            $action,
            new ActionVisibilityContext(new DatatableDefinition('users')),
        ));

        self::assertSame(0, $authorizationChecker->getCallCount());
    }
}

final class RecordingAuthorizationChecker implements AuthorizationCheckerInterface
{
    /**
     * @var array<string, bool>
     */
    private array $grants;

    private int $callCount = 0;

    private mixed $lastAttribute = null;

    private mixed $lastSubject = null;

    /**
     * @param array<string, bool> $grants
     */
    public function __construct(array $grants = [])
    {
        $this->grants = $grants;
    }

    public function isGranted(mixed $attribute, mixed $subject = null, ?AccessDecision $accessDecision = null): bool
    {
        ++$this->callCount;
        $this->lastAttribute = $attribute;
        $this->lastSubject = $subject;

        if (!is_string($attribute)) {
            return false;
        }

        return $this->grants[$attribute] ?? false;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }

    public function getLastAttribute(): mixed
    {
        return $this->lastAttribute;
    }

    public function getLastSubject(): mixed
    {
        return $this->lastSubject;
    }
}
