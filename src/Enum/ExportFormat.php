<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ExportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';

    public static function fromString(string $format): self
    {
        $format = strtolower(trim($format));

        return self::tryFrom($format) ?? throw new \InvalidArgumentException(sprintf('Unsupported export format "%s".', $format));
    }

    public function getExtension(): string
    {
        return $this->value;
    }

    public function getContentType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv; charset=UTF-8',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }
}
