<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Zhortein\DatatableBundle\Enum\ExportFormat;
use Zhortein\DatatableBundle\Enum\ExportMode;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final readonly class DatatableExportRequest
{
    public function __construct(
        private string $datatableName,
        private ExportFormat $format = ExportFormat::Csv,
        private ExportMode $mode = ExportMode::Current,
        private ?string $filename = null,
        private ?DatatableRequest $datatableRequest = null,
    ) {
        if ('' === trim($this->datatableName)) {
            throw new \InvalidArgumentException('The export datatable name cannot be empty.');
        }

        if (null !== $this->filename && '' === trim($this->filename)) {
            throw new \InvalidArgumentException('The export filename cannot be empty when provided.');
        }
    }

    public static function create(
        string $datatableName,
        ExportFormat|string $format = ExportFormat::Csv,
        ExportMode|string $mode = ExportMode::Current,
        ?string $filename = null,
        ?DatatableRequest $datatableRequest = null,
    ): self {
        return new self(
            datatableName: $datatableName,
            format: is_string($format) ? ExportFormat::fromString($format) : $format,
            mode: is_string($mode) ? ExportMode::fromString($mode) : $mode,
            filename: self::normalizeNullableString($filename),
            datatableRequest: $datatableRequest,
        );
    }

    public function getDatatableName(): string
    {
        return $this->datatableName;
    }

    public function getFormat(): ExportFormat
    {
        return $this->format;
    }

    public function getMode(): ExportMode
    {
        return $this->mode;
    }

    public function getFilename(): string
    {
        if (null !== $this->filename) {
            return $this->filename;
        }

        return sprintf(
            '%s.%s',
            $this->sanitizeFilename($this->datatableName),
            $this->format->getFileExtension(),
        );
    }

    public function getDatatableRequest(): ?DatatableRequest
    {
        return $this->datatableRequest;
    }

    public function shouldKeepPagination(): bool
    {
        return $this->mode->shouldKeepPagination();
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $filename) ?? $filename;
        $filename = trim($filename, '-_.');

        return '' === $filename ? 'datatable-export' : $filename;
    }
}
