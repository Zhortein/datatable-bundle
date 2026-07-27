<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

final readonly class DatatableView
{
    public function __construct(
        private DatatableViewMetadata $metadata,
        private DatatableViewState $state,
    ) {
    }

    public function getMetadata(): DatatableViewMetadata
    {
        return $this->metadata;
    }

    public function getState(): DatatableViewState
    {
        return $this->state;
    }

    public function withMetadata(DatatableViewMetadata $metadata): self
    {
        return new self($metadata, $this->state);
    }

    public function withState(DatatableViewState $state): self
    {
        return new self($this->metadata, $state);
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     revision: string,
     *     default: bool,
     *     includePage: bool,
     *     state: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return array_merge(
            $this->metadata->toArray(),
            $this->state->toArray(),
        );
    }
}
