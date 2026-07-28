<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ExportJobStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Expired => true,
            self::Pending, self::Running => false,
        };
    }
}
