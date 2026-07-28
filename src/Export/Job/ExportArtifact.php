<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

/**
 * A completed local artifact waiting to be transferred to result storage.
 */
final readonly class ExportArtifact
{
    public function __construct(
        private string $path,
        private string $filename,
        private string $contentType,
    ) {
        if (!is_file($this->path)) {
            throw new \InvalidArgumentException(sprintf('The export artifact "%s" does not exist.', $this->path));
        }

        if ('' === trim($this->filename)) {
            throw new \InvalidArgumentException('The export artifact filename cannot be empty.');
        }

        if ('' === trim($this->contentType)) {
            throw new \InvalidArgumentException('The export artifact content type cannot be empty.');
        }
    }

    public function getPath(): string
    {
        return $this->path;
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
        $size = filesize($this->path);

        if (false === $size) {
            throw new \RuntimeException(sprintf('Unable to determine the size of export artifact "%s".', $this->path));
        }

        return $size;
    }

    public function delete(): void
    {
        if (is_file($this->path) && !@unlink($this->path)) {
            throw new \RuntimeException(sprintf('Unable to delete export artifact "%s".', $this->path));
        }
    }
}
