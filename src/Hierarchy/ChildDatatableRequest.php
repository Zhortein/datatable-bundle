<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Hierarchy;

/**
 * @internal
 */
final readonly class ChildDatatableRequest
{
    public function __construct(
        private string $instance,
        private int $depth,
    ) {
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }
}
