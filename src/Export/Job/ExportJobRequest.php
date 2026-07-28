<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Export\DatatableExportRequest;

final readonly class ExportJobRequest
{
    /**
     * @param array<string, bool|float|int|string|null> $contextValues
     */
    public function __construct(
        private DatatableExportRequest $exportRequest,
        private string $instance,
        private bool $childDatatable,
        private array $contextValues,
        private string $locale,
        private int $expectedRowCount,
        private int $rowLimit,
    ) {
        if ('' === trim($this->instance)) {
            throw new \InvalidArgumentException('The export job datatable instance cannot be empty.');
        }

        if ('' === trim($this->locale)) {
            throw new \InvalidArgumentException('The export job locale cannot be empty.');
        }

        if ($this->expectedRowCount < 0) {
            throw new \InvalidArgumentException('The expected export job row count cannot be negative.');
        }

        if ($this->rowLimit < 1) {
            throw new \InvalidArgumentException('The export job row limit must be greater than or equal to 1.');
        }

        foreach ($this->contextValues as $name => $value) {
            if ('' === trim($name) || !self::isValidContextValue($value)) {
                throw new \InvalidArgumentException('Export job context values must use non-empty string keys and scalar or null values.');
            }
        }
    }

    public function getExportRequest(): DatatableExportRequest
    {
        return $this->exportRequest;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function isChildDatatable(): bool
    {
        return $this->childDatatable;
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    public function getContextValues(): array
    {
        return $this->contextValues;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getExpectedRowCount(): int
    {
        return $this->expectedRowCount;
    }

    public function getRowLimit(): int
    {
        return $this->rowLimit;
    }

    public function fingerprint(): string
    {
        return hash('sha256', serialize([
            $this->exportRequest,
            $this->instance,
            $this->childDatatable,
            $this->contextValues,
            $this->locale,
        ]));
    }

    private static function isValidContextValue(mixed $value): bool
    {
        return null === $value || is_scalar($value);
    }
}
