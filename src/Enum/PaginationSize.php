<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum PaginationSize: string
{
    case Default = 'default';
    case Small = 'sm';
    case Large = 'lg';

    public static function fromNullableString(?string $size): self
    {
        if (null === $size || '' === trim($size)) {
            return self::Default;
        }

        return self::tryFrom(strtolower(trim($size))) ?? self::Default;
    }
}
