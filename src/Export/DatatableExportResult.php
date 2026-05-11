<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export;

use Zhortein\DatatableBundle\Enum\ExportFormat;

final readonly class DatatableExportResult
{
    public function __construct(
        private string $content,
        private ExportFormat $format,
        private string $filename,
    ) {
        if ('' === $this->filename) {
            throw new \InvalidArgumentException('The export result filename cannot be empty.');
        }
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getFormat(): ExportFormat
    {
        return $this->format;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getContentType(): string
    {
        return $this->format->getContentType();
    }
}
