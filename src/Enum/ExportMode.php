<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ExportMode: string
{
    case Current = 'current';
    case Full = 'full';

    public static function fromString(string $mode): self
    {
        return match (strtolower(trim($mode))) {
            self::Current->value => self::Current,
            self::Full->value => self::Full,
            default => throw new \InvalidArgumentException(sprintf('Invalid export mode "%s". Supported modes: current, full.', $mode)),
        };
    }

    public function shouldKeepPagination(): bool
    {
        return self::Current === $this;
    }
}
