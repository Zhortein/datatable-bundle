<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\View;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Contract\DatatableViewAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Enum\DatatableViewOperation;
use Zhortein\DatatableBundle\Exception\DatatableViewAccessDeniedException;
use Zhortein\DatatableBundle\State\DatatableState;
use Zhortein\DatatableBundle\View\AllowAllDatatableViewAuthorizationChecker;
use Zhortein\DatatableBundle\View\DatatableViewAuthorizationContext;
use Zhortein\DatatableBundle\View\DatatableViewManager;
use Zhortein\DatatableBundle\View\DatatableViewScope;
use Zhortein\DatatableBundle\View\DatatableViewState;
use Zhortein\DatatableBundle\View\InMemoryDatatableViewProvider;

final class DatatableViewManagerTest extends TestCase
{
    public function test_authorized_operations_delegate_to_the_provider(): void
    {
        $manager = new DatatableViewManager(
            new InMemoryDatatableViewProvider(),
            new AllowAllDatatableViewAuthorizationChecker(),
        );
        $scope = DatatableViewScope::create('users', 'users-table');
        $view = $manager->create(
            $scope,
            'opaque-owner',
            'My view',
            DatatableViewState::create(DatatableState::create(searchQuery: 'alice')),
        );

        self::assertSame([$view->getMetadata()], $manager->list($scope, 'opaque-owner'));
        self::assertSame($view, $manager->load(
            $scope,
            'opaque-owner',
            $view->getMetadata()->getIdentifier(),
        ));
    }

    public function test_authorization_receives_the_opaque_owner_scope_operation_and_view(): void
    {
        $provider = new InMemoryDatatableViewProvider();
        $scope = DatatableViewScope::create('users', 'users-table');
        $view = $provider->create(
            $scope,
            'opaque-owner',
            'My view',
            DatatableViewState::create(DatatableState::create()),
        );
        $checker = new class implements DatatableViewAuthorizationCheckerInterface {
            public ?DatatableViewOperation $operation = null;
            public ?DatatableViewAuthorizationContext $context = null;

            public function isGranted(
                DatatableViewOperation $operation,
                DatatableViewAuthorizationContext $context,
            ): bool {
                $this->operation = $operation;
                $this->context = $context;

                return false;
            }
        };
        $manager = new DatatableViewManager($provider, $checker);

        try {
            $manager->load(
                $scope,
                'opaque-owner',
                $view->getMetadata()->getIdentifier(),
            );
            self::fail('A denied operation must throw.');
        } catch (DatatableViewAccessDeniedException) {
            self::assertSame(DatatableViewOperation::Load, $checker->operation);
            self::assertNotNull($checker->context);
            self::assertSame($scope, $checker->context->getScope());
            self::assertSame('opaque-owner', $checker->context->getOwnerIdentifier());
            self::assertSame($view, $checker->context->getView());
        }
    }
}
