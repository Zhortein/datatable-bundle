<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\View;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\View\DatatableViewScope;

final class DatatableViewScopeTest extends TestCase
{
    public function test_storage_keys_are_stable_and_isolated(): void
    {
        $scope = DatatableViewScope::create(
            datatableName: 'orders',
            instance: 'open-orders',
            namespace: 'admin_orders',
            locale: 'fr',
            contextFingerprint: 'tenant-acme',
        );

        self::assertSame($scope->getStorageKey(), DatatableViewScope::create(
            datatableName: 'orders',
            instance: 'open-orders',
            namespace: 'admin_orders',
            locale: 'fr',
            contextFingerprint: 'tenant-acme',
        )->getStorageKey());
        self::assertNotSame($scope->getStorageKey(), DatatableViewScope::create(
            datatableName: 'orders',
            instance: 'open-orders',
            namespace: 'admin_orders',
            locale: 'en',
            contextFingerprint: 'tenant-acme',
        )->getStorageKey());
        self::assertNotSame($scope->getStorageKey(), DatatableViewScope::create(
            datatableName: 'orders',
            instance: 'archived-orders',
            namespace: 'admin_orders',
            locale: 'fr',
            contextFingerprint: 'tenant-acme',
        )->getStorageKey());
        self::assertNotSame($scope->getStorageKey(), DatatableViewScope::create(
            datatableName: 'orders',
            instance: 'open-orders',
            namespace: 'admin_orders',
            locale: 'fr',
            contextFingerprint: 'tenant-isatis',
        )->getStorageKey());
    }

    public function test_control_characters_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DatatableViewScope::create('orders', "open\norders");
    }
}
