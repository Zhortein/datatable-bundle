<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

final readonly class RunExportJobHandler
{
    public function __construct(
        private ExportJobRunner $runner,
    ) {
    }

    public function __invoke(RunExportJobMessage $message): void
    {
        $this->runner->run($message->getJobIdentifier());
    }
}
