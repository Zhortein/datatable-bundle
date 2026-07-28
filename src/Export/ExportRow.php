<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

/**
 * One normalized provider row and its optional server-side source.
 */
final readonly class ExportRow
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private array $values,
        private mixed $source = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function getSource(): mixed
    {
        return $this->source;
    }
}
