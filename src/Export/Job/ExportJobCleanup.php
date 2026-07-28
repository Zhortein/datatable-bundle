<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Contract\ExportJobClockInterface;
use Zhortein\DatatableBundle\Contract\ExportJobRepositoryInterface;
use Zhortein\DatatableBundle\Contract\ExportJobResultStorageInterface;

final readonly class ExportJobCleanup
{
    public function __construct(
        private ExportJobRepositoryInterface $repository,
        private ExportJobResultStorageInterface $resultStorage,
        private ExportJobClockInterface $clock,
    ) {
    }

    public function cleanup(int $limit = 100): int
    {
        $now = $this->clock->now();
        $cleaned = 0;

        foreach ($this->repository->findExpired($now, $limit) as $job) {
            $result = $job->getResult();

            if (null !== $result) {
                $this->resultStorage->delete($result);
            }

            $this->repository->save($job->expire($now));
            ++$cleaned;
        }

        return $cleaned;
    }
}
