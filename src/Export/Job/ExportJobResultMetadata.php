<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

final readonly class ExportJobResultMetadata
{
    public function __construct(
        private string $storageKey,
        private string $filename,
        private string $contentType,
        private int $size,
        private \DateTimeImmutable $createdAt,
    ) {
        if ('' === trim($this->storageKey)) {
            throw new \InvalidArgumentException('The export result storage key cannot be empty.');
        }

        if ('' === trim($this->filename)) {
            throw new \InvalidArgumentException('The export result filename cannot be empty.');
        }

        if ('' === trim($this->contentType)) {
            throw new \InvalidArgumentException('The export result content type cannot be empty.');
        }

        if ($this->size < 0) {
            throw new \InvalidArgumentException('The export result size cannot be negative.');
        }
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
