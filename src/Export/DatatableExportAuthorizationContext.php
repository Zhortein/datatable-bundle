<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Symfony\Component\HttpFoundation\Request;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Enum\ExportMode;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final readonly class DatatableExportAuthorizationContext
{
    public function __construct(
        private DatatableDefinition $definition,
        private DatatableExportRequest $exportRequest,
        private Request $request,
        private string $instance,
        private bool $childDatatable = false,
    ) {
    }

    public function getDefinition(): DatatableDefinition
    {
        return $this->definition;
    }

    public function getExportRequest(): DatatableExportRequest
    {
        return $this->exportRequest;
    }

    public function getFormat(): ExportFormat
    {
        return $this->exportRequest->getFormat();
    }

    public function getMode(): ExportMode
    {
        return $this->exportRequest->getMode();
    }

    public function getDatatableRequest(): ?DatatableRequest
    {
        return $this->exportRequest->getDatatableRequest();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getDatatableContext(): DatatableContext
    {
        return $this->definition->getContext();
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function isChildDatatable(): bool
    {
        return $this->childDatatable;
    }
}
