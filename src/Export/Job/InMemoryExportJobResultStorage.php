<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Contract\ExportJobResultStorageInterface;

/**
 * Deterministic process-local implementation intended for tests and examples.
 */
final class InMemoryExportJobResultStorage implements ExportJobResultStorageInterface
{
    /**
     * @var array<string, string>
     */
    private array $contents = [];

    public function __construct(
        private readonly int $chunkSize = 8192,
    ) {
        if ($this->chunkSize < 1) {
            throw new \InvalidArgumentException('The export result storage chunk size must be greater than or equal to 1.');
        }
    }

    public function store(
        ExportJobIdentifier $identifier,
        ExportArtifact $artifact,
        \DateTimeImmutable $createdAt,
    ): ExportJobResultMetadata {
        $content = file_get_contents($artifact->getPath());

        if (false === $content) {
            throw new \RuntimeException(sprintf('Unable to read export artifact "%s".', $artifact->getPath()));
        }

        $storageKey = $identifier->toString();
        $this->contents[$storageKey] = $content;

        return new ExportJobResultMetadata(
            storageKey: $storageKey,
            filename: $artifact->getFilename(),
            contentType: $artifact->getContentType(),
            size: strlen($content),
            createdAt: $createdAt,
        );
    }

    public function read(ExportJobResultMetadata $metadata): iterable
    {
        $content = $this->contents[$metadata->getStorageKey()] ?? null;

        if (null === $content) {
            throw new \RuntimeException('The export job result is no longer available.');
        }

        $length = strlen($content);

        for ($offset = 0; $offset < $length; $offset += $this->chunkSize) {
            yield substr($content, $offset, $this->chunkSize);
        }
    }

    public function delete(ExportJobResultMetadata $metadata): void
    {
        unset($this->contents[$metadata->getStorageKey()]);
    }
}
