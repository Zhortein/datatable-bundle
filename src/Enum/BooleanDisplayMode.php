<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum BooleanDisplayMode: string
{
    case Badge = 'badge';
    case Icon = 'icon';
    case Switch = 'switch';
    case Text = 'text';

    public static function fromNullableString(?string $mode): self
    {
        if (null === $mode || '' === trim($mode)) {
            return self::Badge;
        }

        return self::tryFrom(strtolower(trim($mode))) ?? self::Badge;
    }
}
