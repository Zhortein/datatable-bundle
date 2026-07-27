<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Hierarchy;

use Zhortein\DatatableBundle\Context\DatatableContext;

/**
 * @internal
 */
final readonly class ResolvedChildDatatable
{
    public function __construct(
        private string $name,
        private string $instance,
        private int $depth,
        private DatatableContext $context,
        private string $contextToken,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getContext(): DatatableContext
    {
        return $this->context;
    }

    public function getContextToken(): string
    {
        return $this->contextToken;
    }
}
