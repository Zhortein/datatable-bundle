<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    public static function fromString(string $direction): self
    {
        return match (strtolower(trim($direction))) {
            self::Asc->value => self::Asc,
            self::Desc->value => self::Desc,
            default => throw new \InvalidArgumentException(sprintf('Invalid sort direction "%s". Expected "asc" or "desc".', $direction)),
        };
    }
}
