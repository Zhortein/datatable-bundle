<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Hierarchy;

use Zhortein\DatatableBundle\Context\DatatableContext;

final readonly class ChildDatatableAuthorizationContext
{
    public function __construct(
        private string $childDatatableName,
        private string $childInstance,
        private int $depth,
        private DatatableContext $context,
    ) {
    }

    public function getChildDatatableName(): string
    {
        return $this->childDatatableName;
    }

    public function getChildInstance(): string
    {
        return $this->childInstance;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getContext(): DatatableContext
    {
        return $this->context;
    }
}
