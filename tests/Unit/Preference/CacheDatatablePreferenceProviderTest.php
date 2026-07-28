<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Preference;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zhortein\DatatableBundle\Exception\DatatablePreferenceStorageException;
use Zhortein\DatatableBundle\Preference\CacheDatatablePreferenceProvider;
use Zhortein\DatatableBundle\Preference\DatatablePreference;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceScope;

final class CacheDatatablePreferenceProviderTest extends TestCase
{
    public function test_it_saves_loads_and_resets_scoped_preferences(): void
    {
        $provider = new CacheDatatablePreferenceProvider(new ArrayAdapter(), 3600);
        $scope = $this->createScope();
        $preference = DatatablePreference::create(
            pageSize: 50,
            visibleColumns: ['email'],
            filters: ['status' => 'active'],
        );

        self::assertTrue($provider->getPreferenceForScope($scope)->isEmpty());

        $provider->savePreference($scope, $preference);

        self::assertSame(
            $preference->toStorageArray(),
            $provider->getPreferenceForScope($scope)->toStorageArray(),
        );

        $provider->resetPreference($scope);

        self::assertTrue($provider->getPreferenceForScope($scope)->isEmpty());
    }

    public function test_schema_versions_are_isolated(): void
    {
        $provider = new CacheDatatablePreferenceProvider(new ArrayAdapter(), 3600);
        $provider->savePreference(
            $this->createScope('schema-1'),
            DatatablePreference::create(pageSize: 100),
        );

        self::assertTrue(
            $provider->getPreferenceForScope($this->createScope('schema-2'))->isEmpty(),
        );
    }

    public function test_read_failures_degrade_to_an_empty_preference(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->method('getItem')
            ->willThrowException(new \RuntimeException('cache unavailable'))
        ;
        $provider = new CacheDatatablePreferenceProvider($cache, 3600);

        self::assertTrue(
            $provider->getPreferenceForScope($this->createScope())->isEmpty(),
        );
    }

    public function test_write_failures_are_normalized(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->method('getItem')
            ->willThrowException(new \RuntimeException('cache unavailable'))
        ;
        $provider = new CacheDatatablePreferenceProvider($cache, 3600);

        $this->expectException(DatatablePreferenceStorageException::class);
        $this->expectExceptionMessage('The datatable preference could not be saved.');

        $provider->savePreference(
            $this->createScope(),
            DatatablePreference::create(pageSize: 50),
        );
    }

    private function createScope(string $schemaVersion = 'schema-1'): DatatablePreferenceScope
    {
        return DatatablePreferenceScope::create(
            ownerIdentifier: 'user-1',
            datatableName: 'users',
            instance: 'users',
            routeScope: 'admin_users',
            locale: 'en',
            schemaVersion: $schemaVersion,
        );
    }
}
