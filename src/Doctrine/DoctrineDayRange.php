<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;

/**
 * The day a date-only filter value names, from its first instant up to the first instant of the next day.
 *
 * A column holding a time of day compares with the whole day only through these two bounds: compared with the date
 * alone, it would meet midnight and leave the rest of the day out.
 *
 * @internal
 */
final readonly class DoctrineDayRange
{
    private const string FORMAT = 'Y-m-d';

    /** The parameter type binding a bound to a column of each date type, the instant otherwise. */
    private const array PARAMETER_TYPES = [
        Types::DATE_MUTABLE => Types::DATE_IMMUTABLE,
        Types::DATE_IMMUTABLE => Types::DATE_IMMUTABLE,
        Types::DATETIMETZ_MUTABLE => Types::DATETIMETZ_IMMUTABLE,
        Types::DATETIMETZ_IMMUTABLE => Types::DATETIMETZ_IMMUTABLE,
    ];

    private function __construct(
        private \DateTimeImmutable $start,
        private string $parameterType,
    ) {
    }

    /**
     * The day a value names as a Y-m-d date, or null when it names none.
     *
     * @param string|null $columnType the Doctrine type of the column the day is compared with
     */
    public static function fromValue(mixed $value, ?string $columnType): ?self
    {
        if (!is_string($value)) {
            return null;
        }

        $start = \DateTimeImmutable::createFromFormat('!'.self::FORMAT, $value);

        if (!$start instanceof \DateTimeImmutable || $start->format(self::FORMAT) !== $value) {
            return null;
        }

        return new self($start, self::PARAMETER_TYPES[$columnType ?? ''] ?? Types::DATETIME_IMMUTABLE);
    }

    /**
     * Binds the first instant of the day and returns its placeholder.
     */
    public function bindStart(QueryBuilder $queryBuilder, string $parameterName): string
    {
        return $this->bind($queryBuilder, $parameterName, $this->start);
    }

    /**
     * Binds the first instant of the next day, the excluded end of the day, and returns its placeholder.
     */
    public function bindNextDay(QueryBuilder $queryBuilder, string $parameterName): string
    {
        return $this->bind($queryBuilder, $parameterName, $this->start->modify('+1 day'));
    }

    private function bind(QueryBuilder $queryBuilder, string $parameterName, \DateTimeImmutable $instant): string
    {
        $queryBuilder->setParameter($parameterName, $instant, $this->parameterType);

        return ':'.$parameterName;
    }
}
