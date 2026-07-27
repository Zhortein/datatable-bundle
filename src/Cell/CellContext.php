<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Cell;

use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

/**
 * Complete server-side context for one resolved datatable cell.
 *
 * This object is intended for PHP value resolvers and Twig cell templates. It
 * must never be serialized into browser attributes or JSON implicitly.
 */
final readonly class CellContext
{
    /**
     * @param array<string, mixed> $row
     */
    public function __construct(
        private mixed $value,
        private array $row,
        private mixed $source,
        private ?string $rowIdentifier,
        private ColumnDefinition $column,
        private DatatableDefinition $definition,
        private DatatableContext $datatableContext,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRow(): array
    {
        return $this->row;
    }

    public function getSource(): mixed
    {
        return $this->source;
    }

    public function hasSource(): bool
    {
        return null !== $this->source;
    }

    public function getRowIdentifier(): ?string
    {
        return $this->rowIdentifier;
    }

    public function getColumn(): ColumnDefinition
    {
        return $this->column;
    }

    public function getDefinition(): DatatableDefinition
    {
        return $this->definition;
    }

    public function getDatatableContext(): DatatableContext
    {
        return $this->datatableContext;
    }

    public function withValue(mixed $value): self
    {
        return new self(
            value: $value,
            row: $this->row,
            source: $this->source,
            rowIdentifier: $this->rowIdentifier,
            column: $this->column,
            definition: $this->definition,
            datatableContext: $this->datatableContext,
        );
    }
}
