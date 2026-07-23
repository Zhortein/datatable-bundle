<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Export\ExportableColumnResolver;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class ExportableColumnResolverTest extends TestCase
{
    public function test_default_policy_follows_definition_and_runtime_visibility(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.id', visible: false)
            ->addColumn('e.email')
            ->addColumn('e.displayName')
            ->addColumn('e.enabled')
        ;

        $request = new DatatableExportRequest(
            datatableName: 'users',
            datatableRequest: DatatableRequest::create(
                visibleColumns: ['e.email', 'e.displayName'],
                hiddenColumns: ['e.displayName'],
            ),
        );

        self::assertSame(
            ['e.email'],
            $this->resolveColumnNames($request, $definition),
        );
    }

    public function test_explicit_policy_overrides_definition_and_runtime_visibility(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->addColumn('e.id', visible: false)
            ->addColumn('e.internalReference', visible: false, exportable: true)
            ->addColumn('e.email')
            ->addColumn('e.displayName')
            ->addColumn('e.token', exportable: false)
            ->addColumn('e.auditCode', exportable: true)
        ;

        $request = new DatatableExportRequest(
            datatableName: 'users',
            datatableRequest: DatatableRequest::create(
                visibleColumns: ['e.email', 'e.displayName'],
                hiddenColumns: ['e.displayName', 'e.auditCode'],
            ),
        );

        self::assertSame(
            ['e.internalReference', 'e.email', 'e.auditCode'],
            $this->resolveColumnNames($request, $definition),
        );
    }

    /**
     * @return list<string>
     */
    private function resolveColumnNames(
        DatatableExportRequest $request,
        DatatableDefinition $definition,
    ): array {
        return array_map(
            static fn (ColumnDefinition $column): string => $column->getName(),
            new ExportableColumnResolver()->resolve($request, $definition),
        );
    }
}
