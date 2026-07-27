<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Enum\ExportMode;
use Zhortein\DatatableBundle\Export\DatatableExportAuthorizationContext;
use Zhortein\DatatableBundle\Export\DatatableExportRequest;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class DatatableExportAuthorizationContextTest extends TestCase
{
    public function test_it_exposes_definition_export_state_and_application_context(): void
    {
        $definition = new DatatableDefinition('order-lines');
        $definition->setContext(new DatatableContext(['orderId' => 42]));
        $datatableRequest = DatatableRequest::create(searchQuery: 'open');
        $exportRequest = DatatableExportRequest::create(
            datatableName: 'order-lines',
            format: ExportFormat::Xlsx,
            mode: ExportMode::Full,
            datatableRequest: $datatableRequest,
        );
        $request = new Request(attributes: ['_route' => 'zhortein_datatable_export']);
        $context = new DatatableExportAuthorizationContext(
            definition: $definition,
            exportRequest: $exportRequest,
            request: $request,
            instance: 'orders--42--lines',
            childDatatable: true,
        );

        self::assertSame($definition, $context->getDefinition());
        self::assertSame($exportRequest, $context->getExportRequest());
        self::assertSame(ExportFormat::Xlsx, $context->getFormat());
        self::assertSame(ExportMode::Full, $context->getMode());
        self::assertSame($datatableRequest, $context->getDatatableRequest());
        self::assertSame($request, $context->getRequest());
        self::assertSame(42, $context->getDatatableContext()->get('orderId'));
        self::assertSame('orders--42--lines', $context->getInstance());
        self::assertTrue($context->isChildDatatable());
    }
}
