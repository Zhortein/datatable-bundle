<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ActionIconPosition: string
{
    case Before = 'before';
    case After = 'after';

    public static function fromNullableString(?string $position): self
    {
        if (null === $position || '' === trim($position)) {
            return self::Before;
        }

        return self::tryFrom(strtolower(trim($position))) ?? self::Before;
    }
}
