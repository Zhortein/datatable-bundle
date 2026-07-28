<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

final readonly class RunExportJobMessage
{
    private string $jobIdentifier;

    public function __construct(
        string $jobIdentifier,
    ) {
        $this->jobIdentifier = new ExportJobIdentifier($jobIdentifier)->toString();
    }

    public static function fromIdentifier(ExportJobIdentifier $identifier): self
    {
        return new self($identifier->toString());
    }

    public function getJobIdentifier(): ExportJobIdentifier
    {
        return new ExportJobIdentifier($this->jobIdentifier);
    }
}
