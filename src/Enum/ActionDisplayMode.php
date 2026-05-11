<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ActionDisplayMode: string
{
    case Inline = 'inline';
    case Dropdown = 'dropdown';
    case List = 'list';

    public static function fromNullableString(?string $mode): self
    {
        if (null === $mode || '' === trim($mode)) {
            return self::Inline;
        }

        return self::tryFrom(strtolower(trim($mode))) ?? self::Inline;
    }
}
