<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\View;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Exception\DatatableViewConflictException;
use Zhortein\DatatableBundle\State\DatatableState;
use Zhortein\DatatableBundle\View\DatatableViewState;
use Zhortein\DatatableBundle\View\DatatableViewScope;
use Zhortein\DatatableBundle\View\InMemoryDatatableViewProvider;

final class InMemoryDatatableViewProviderTest extends TestCase
{
    public function test_it_supports_the_complete_view_lifecycle(): void
    {
        $provider = new InMemoryDatatableViewProvider();
        $scope = DatatableViewScope::create('users', 'users-table', 'admin_users', 'fr');
        $state = DatatableViewState::create(DatatableState::create(searchQuery: 'alice'));

        $created = $provider->create($scope, 'owner-1', 'Active users', $state, true);

        self::assertTrue($created->getMetadata()->isDefault());
        self::assertSame('1', $created->getMetadata()->getRevision());
        self::assertSame([$created->getMetadata()], $provider->list($scope, 'owner-1'));
        self::assertSame($created, $provider->load(
            $scope,
            'owner-1',
            $created->getMetadata()->getIdentifier(),
        ));

        $renamed = $provider->rename(
            $scope,
            'owner-1',
            $created->getMetadata()->getIdentifier(),
            'Enabled users',
            '1',
        );
        self::assertSame('Enabled users', $renamed->getMetadata()->getName());
        self::assertSame('2', $renamed->getMetadata()->getRevision());

        $updated = $provider->update(
            $scope,
            'owner-1',
            $renamed->getMetadata()->getIdentifier(),
            DatatableViewState::create(DatatableState::create(searchQuery: 'bob')),
            '2',
        );
        self::assertSame('bob', $updated->getState()->getState()->getSearchQuery());
        self::assertSame('3', $updated->getMetadata()->getRevision());

        $provider->delete(
            $scope,
            'owner-1',
            $updated->getMetadata()->getIdentifier(),
            '3',
        );
        self::assertSame([], $provider->list($scope, 'owner-1'));
    }

    public function test_it_isolates_owners_and_scopes(): void
    {
        $provider = new InMemoryDatatableViewProvider();
        $scope = DatatableViewScope::create('users', 'users-table', 'route-a', 'fr');
        $otherScope = DatatableViewScope::create('users', 'users-table', 'route-b', 'fr');
        $view = $provider->create(
            $scope,
            'owner-1',
            'My view',
            DatatableViewState::create(DatatableState::create()),
        );

        self::assertNotNull($provider->load($scope, 'owner-1', $view->getMetadata()->getIdentifier()));
        self::assertNull($provider->load($scope, 'owner-2', $view->getMetadata()->getIdentifier()));
        self::assertNull($provider->load($otherScope, 'owner-1', $view->getMetadata()->getIdentifier()));
    }

    public function test_setting_a_default_unsets_the_previous_default_and_updates_its_revision(): void
    {
        $provider = new InMemoryDatatableViewProvider();
        $scope = DatatableViewScope::create('users', 'users-table');
        $state = DatatableViewState::create(DatatableState::create());
        $first = $provider->create($scope, 'owner-1', 'First', $state, true);
        $second = $provider->create($scope, 'owner-1', 'Second', $state);

        $second = $provider->setDefault(
            $scope,
            'owner-1',
            $second->getMetadata()->getIdentifier(),
            $second->getMetadata()->getRevision(),
        );
        $first = $provider->load($scope, 'owner-1', $first->getMetadata()->getIdentifier());

        self::assertNotNull($first);
        self::assertFalse($first->getMetadata()->isDefault());
        self::assertSame('2', $first->getMetadata()->getRevision());
        self::assertTrue($second->getMetadata()->isDefault());
    }

    public function test_stale_revisions_are_rejected(): void
    {
        $provider = new InMemoryDatatableViewProvider();
        $scope = DatatableViewScope::create('users', 'users-table');
        $view = $provider->create(
            $scope,
            'owner-1',
            'My view',
            DatatableViewState::create(DatatableState::create()),
        );

        $this->expectException(DatatableViewConflictException::class);

        $provider->rename(
            $scope,
            'owner-1',
            $view->getMetadata()->getIdentifier(),
            'Renamed',
            'stale',
        );
    }

    public function test_names_are_unique_case_insensitively_within_a_partition(): void
    {
        $provider = new InMemoryDatatableViewProvider();
        $scope = DatatableViewScope::create('users', 'users-table');
        $state = DatatableViewState::create(DatatableState::create());
        $provider->create($scope, 'owner-1', 'My view', $state);

        $this->expectException(DatatableViewConflictException::class);

        $provider->create($scope, 'owner-1', 'my VIEW', $state);
    }
}
