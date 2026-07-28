<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Preference;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceScope;

final class DatatablePreferenceScopeTest extends TestCase
{
    public function test_storage_keys_are_isolated_by_every_scope_dimension(): void
    {
        $base = DatatablePreferenceScope::create(
            ownerIdentifier: 'user-1',
            datatableName: 'orders',
            instance: 'open-orders',
            routeScope: 'admin_orders',
            namespace: 'tenant-a',
            locale: 'fr',
            schemaVersion: 'schema-1',
            contextFingerprint: 'context-a',
        );

        $variants = [
            ['ownerIdentifier' => 'user-2'],
            ['datatableName' => 'invoices'],
            ['instance' => 'closed-orders'],
            ['routeScope' => 'customer_orders'],
            ['namespace' => 'tenant-b'],
            ['locale' => 'en'],
            ['schemaVersion' => 'schema-2'],
            ['contextFingerprint' => 'context-b'],
        ];

        foreach ($variants as $variant) {
            $scope = DatatablePreferenceScope::create(
                ownerIdentifier: $variant['ownerIdentifier'] ?? 'user-1',
                datatableName: $variant['datatableName'] ?? 'orders',
                instance: $variant['instance'] ?? 'open-orders',
                routeScope: $variant['routeScope'] ?? 'admin_orders',
                namespace: $variant['namespace'] ?? 'tenant-a',
                locale: $variant['locale'] ?? 'fr',
                schemaVersion: $variant['schemaVersion'] ?? 'schema-1',
                contextFingerprint: $variant['contextFingerprint'] ?? 'context-a',
            );

            self::assertNotSame($base->getStorageKey(), $scope->getStorageKey());
        }
    }
}
