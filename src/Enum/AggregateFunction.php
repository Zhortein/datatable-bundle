<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum AggregateFunction: string
{
    case Count = 'count';
    case Sum = 'sum';
    case Min = 'min';
    case Max = 'max';
    case Avg = 'avg';

    public function getDqlFunction(): string
    {
        return match ($this) {
            self::Count => 'COUNT',
            self::Sum => 'SUM',
            self::Min => 'MIN',
            self::Max => 'MAX',
            self::Avg => 'AVG',
        };
    }

    public static function fromNullableString(?string $function): self
    {
        if (null === $function || '' === trim($function)) {
            return self::Count;
        }

        return self::tryFrom(strtolower(trim($function))) ?? self::Count;
    }
}
