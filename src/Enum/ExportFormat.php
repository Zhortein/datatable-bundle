<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ExportFormat: string
{
    case Csv = 'csv';

    public static function fromString(string $format): self
    {
        return match (strtolower(trim($format))) {
            self::Csv->value => self::Csv,
            default => throw new \InvalidArgumentException(sprintf('Invalid export format "%s". Supported formats: csv.', $format)),
        };
    }

    public function getContentType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv; charset=UTF-8',
        };
    }

    public function getFileExtension(): string
    {
        return $this->value;
    }
}
