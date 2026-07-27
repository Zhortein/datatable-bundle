<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Sorting;

use Zhortein\DatatableBundle\Enum\SortDirection;

/**
 * One ordered sorting criterion.
 */
final readonly class SortCriterion
{
    public const int MAX_CRITERIA = 8;

    private string $field;

    public function __construct(
        string $field,
        private SortDirection $direction = SortDirection::Asc,
    ) {
        $field = trim($field);

        if ('' === $field) {
            throw new \InvalidArgumentException('The sort criterion field must not be empty.');
        }

        $this->field = $field;
    }

    public static function create(
        string $field,
        SortDirection|string $direction = SortDirection::Asc,
    ): self {
        return new self(
            field: $field,
            direction: is_string($direction) ? SortDirection::fromString($direction) : $direction,
        );
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getDirection(): SortDirection
    {
        return $this->direction;
    }

    /**
     * @return array{field: string, direction: string}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction->value,
        ];
    }

    /**
     * @param array<array-key, mixed> $criteria
     *
     * @return list<self>
     */
    public static function normalizeList(array $criteria): array
    {
        if (!array_is_list($criteria)) {
            throw new \InvalidArgumentException('Sort criteria must be provided as a list.');
        }

        $normalized = [];
        $fields = [];

        foreach ($criteria as $criterion) {
            if (!$criterion instanceof self) {
                throw new \InvalidArgumentException('Every sort criterion must be a SortCriterion instance.');
            }

            if (isset($fields[$criterion->getField()])) {
                continue;
            }

            if (self::MAX_CRITERIA === count($normalized)) {
                throw new \InvalidArgumentException(sprintf(
                    'A datatable cannot use more than %d sort criteria.',
                    self::MAX_CRITERIA,
                ));
            }

            $normalized[] = $criterion;
            $fields[$criterion->getField()] = true;
        }

        return $normalized;
    }
}
