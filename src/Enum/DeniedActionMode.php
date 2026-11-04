<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

/**
 * What a row shows of an action its visibility checker denies: nothing, or the action disabled.
 */
enum DeniedActionMode: string
{
    case Hide = 'hide';
    case Disable = 'disable';

    public static function fromNullableString(?string $mode): self
    {
        if (null === $mode || '' === trim($mode)) {
            return self::Hide;
        }

        return self::tryFrom(strtolower(trim($mode))) ?? self::Hide;
    }
}
