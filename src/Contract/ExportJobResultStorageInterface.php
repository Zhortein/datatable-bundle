<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Export\Job\ExportArtifact;
use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;
use Zhortein\DatatableBundle\Export\Job\ExportJobResultMetadata;

interface ExportJobResultStorageInterface
{
    public function store(
        ExportJobIdentifier $identifier,
        ExportArtifact $artifact,
        \DateTimeImmutable $createdAt,
    ): ExportJobResultMetadata;

    /**
     * @return iterable<string>
     */
    public function read(ExportJobResultMetadata $metadata): iterable;

    public function delete(ExportJobResultMetadata $metadata): void;
}
