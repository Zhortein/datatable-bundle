<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\ExpressionInterface;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;

final class DoctrineExpressionApplier
{
    /** The field types whose date-only values name a whole day. */
    private const array DATE_TYPES = [FilterType::Date, FilterType::DateRange];

    private int $parameterIndex = 0;

    public function __construct(
        private readonly DoctrineFieldReferenceResolver $fieldReferenceResolver = new DoctrineFieldReferenceResolver(),
        private readonly DoctrineFieldMetadataResolver $fieldMetadataResolver = new DoctrineFieldMetadataResolver(),
    ) {
    }

    /**
     * @param class-string $entityClass
     */
    public function apply(
        QueryBuilder $queryBuilder,
        AdvancedFilterExpression $expression,
        DatatableDefinition $definition,
        EntityManagerInterface $entityManager,
        string $entityClass,
    ): void {
        $this->parameterIndex = 0;

        $doctrineExpression = $this->applyExpression(
            queryBuilder: $queryBuilder,
            expression: $expression->root,
            definition: $definition,
            entityManager: $entityManager,
            entityClass: $entityClass,
        );

        if (null !== $doctrineExpression) {
            $queryBuilder->andWhere($doctrineExpression);
        }
    }

    /**
     * @param class-string $entityClass
     */
    private function applyExpression(
        QueryBuilder $queryBuilder,
        ExpressionInterface $expression,
        DatatableDefinition $definition,
        EntityManagerInterface $entityManager,
        string $entityClass,
    ): string|object|null {
        if ($expression instanceof Group) {
            return $this->applyGroup($queryBuilder, $expression, $definition, $entityManager, $entityClass);
        }

        if ($expression instanceof Condition) {
            return $this->applyCondition($queryBuilder, $expression, $definition, $entityManager, $entityClass);
        }

        return null;
    }

    /**
     * @param class-string $entityClass
     */
    private function applyGroup(
        QueryBuilder $queryBuilder,
        Group $group,
        DatatableDefinition $definition,
        EntityManagerInterface $entityManager,
        string $entityClass,
    ): string|object|null {
        $expressions = [];

        foreach ($group->children as $child) {
            $doctrineExpression = $this->applyExpression($queryBuilder, $child, $definition, $entityManager, $entityClass);

            if (null !== $doctrineExpression) {
                $expressions[] = $doctrineExpression;
            }
        }

        if ([] === $expressions) {
            return null;
        }

        if (1 === count($expressions)) {
            return $expressions[0];
        }

        /** @var array<int, \Doctrine\ORM\Query\Expr\Andx|\Doctrine\ORM\Query\Expr\Comparison|\Doctrine\ORM\Query\Expr\Func|\Doctrine\ORM\Query\Expr\Orx|string> $expressions */
        return match ($group->logic) {
            LogicOperator::And => $queryBuilder->expr()->andX(...$expressions),
            LogicOperator::Or => $queryBuilder->expr()->orX(...$expressions),
        };
    }

    /**
     * @param class-string $entityClass
     */
    private function applyCondition(
        QueryBuilder $queryBuilder,
        Condition $condition,
        DatatableDefinition $definition,
        EntityManagerInterface $entityManager,
        string $entityClass,
    ): string|object|null {
        $fieldDefinition = $definition->getAdvancedFilterFields()[$condition->field] ?? null;
        $declaredField = $fieldDefinition?->getField() ?? $condition->field;

        try {
            $reference = $this->fieldReferenceResolver->normalize($declaredField, $definition);
        } catch (\InvalidArgumentException) {
            return null;
        }

        if (!$this->fieldMetadataResolver->hasField($entityManager, $entityClass, $definition, $reference)) {
            return null;
        }

        $field = $reference->toString();
        $parameterName = sprintf('advanced_filter_%d', $this->parameterIndex++);

        if (in_array($fieldDefinition?->getType(), self::DATE_TYPES, true)) {
            $columnType = $this->fieldMetadataResolver->getTypeOfField(
                entityManager: $entityManager,
                mainEntityClass: $entityClass,
                definition: $definition,
                reference: $reference,
            );
            $dayExpression = $this->createDayExpression($queryBuilder, $condition, $field, $parameterName, $columnType);

            if (null !== $dayExpression) {
                return $dayExpression;
            }
        }

        return match ($condition->operator) {
            ComparisonOperator::Equals => $this->createEqExpression($queryBuilder, $field, $parameterName, $condition->value),
            ComparisonOperator::NotEquals => $this->createNeqExpression($queryBuilder, $field, $parameterName, $condition->value),
            ComparisonOperator::GreaterThan => $this->createGtExpression($queryBuilder, $field, $parameterName, $condition->value),
            ComparisonOperator::GreaterThanOrEquals => $this->createGteExpression($queryBuilder, $field, $parameterName, $condition->value),
            ComparisonOperator::LessThan => $this->createLtExpression($queryBuilder, $field, $parameterName, $condition->value),
            ComparisonOperator::LessThanOrEquals => $this->createLteExpression($queryBuilder, $field, $parameterName, $condition->value),
            ComparisonOperator::In => $this->createInExpression($queryBuilder, $field, $parameterName, $condition->value),
            ComparisonOperator::NotIn => $this->createNotInExpression($queryBuilder, $field, $parameterName, $condition->value),
            ComparisonOperator::IsNull => $queryBuilder->expr()->isNull($field),
            ComparisonOperator::IsNotNull => $queryBuilder->expr()->isNotNull($field),
            ComparisonOperator::Between => $this->createBetweenExpression($queryBuilder, $field, $parameterName, $condition->value),
            ComparisonOperator::StartsWith => $this->createLikeExpression($queryBuilder, $field, $parameterName, (is_scalar($condition->value) ? (string) $condition->value : '').'%'),
            ComparisonOperator::EndsWith => $this->createLikeExpression($queryBuilder, $field, $parameterName, '%'.(is_scalar($condition->value) ? (string) $condition->value : '')),
            ComparisonOperator::Contains => $this->createLikeExpression($queryBuilder, $field, $parameterName, '%'.(is_scalar($condition->value) ? (string) $condition->value : '').'%'),
            ComparisonOperator::NotContains => $this->createNotLikeExpression($queryBuilder, $field, $parameterName, '%'.(is_scalar($condition->value) ? (string) $condition->value : '').'%'),
        };
    }

    private function createEqExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): object
    {
        $queryBuilder->setParameter($parameterName, $value);

        return $queryBuilder->expr()->eq($field, ':'.$parameterName);
    }

    private function createNeqExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): object
    {
        $queryBuilder->setParameter($parameterName, $value);

        return $queryBuilder->expr()->neq($field, ':'.$parameterName);
    }

    private function createGtExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): object
    {
        $queryBuilder->setParameter($parameterName, $value);

        return $queryBuilder->expr()->gt($field, ':'.$parameterName);
    }

    private function createGteExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): object
    {
        $queryBuilder->setParameter($parameterName, $value);

        return $queryBuilder->expr()->gte($field, ':'.$parameterName);
    }

    private function createLtExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): object
    {
        $queryBuilder->setParameter($parameterName, $value);

        return $queryBuilder->expr()->lt($field, ':'.$parameterName);
    }

    private function createLteExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): object
    {
        $queryBuilder->setParameter($parameterName, $value);

        return $queryBuilder->expr()->lte($field, ':'.$parameterName);
    }

    private function createInExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): object
    {
        $queryBuilder->setParameter($parameterName, $value);

        return $queryBuilder->expr()->in($field, ':'.$parameterName);
    }

    private function createNotInExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): object
    {
        $queryBuilder->setParameter($parameterName, $value);

        return $queryBuilder->expr()->notIn($field, ':'.$parameterName);
    }

    private function createBetweenExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): ?string
    {
        $bounds = $this->betweenBounds($value);

        if (null === $bounds) {
            return null;
        }

        [$start, $end] = $bounds;
        $startParam = $parameterName.'_start';
        $endParam = $parameterName.'_end';

        $queryBuilder->setParameter($startParam, $start);
        $queryBuilder->setParameter($endParam, $end);

        return $queryBuilder->expr()->between($field, ':'.$startParam, ':'.$endParam);
    }

    /**
     * A comparison of the field with the whole day a date-only value names, or null when the condition names no day.
     */
    private function createDayExpression(
        QueryBuilder $queryBuilder,
        Condition $condition,
        string $field,
        string $parameterName,
        ?string $columnType,
    ): ?object {
        if (ComparisonOperator::Between === $condition->operator) {
            [$start, $end] = $this->betweenBounds($condition->value) ?? [null, null];

            return $this->createDayBetweenExpression($queryBuilder, $field, $parameterName, $start, $end, $columnType);
        }

        $day = DoctrineDayRange::fromValue($condition->value, $columnType);

        if (null === $day) {
            return null;
        }

        $expr = $queryBuilder->expr();
        $start = $parameterName.'_start';
        $end = $parameterName.'_end';

        return match ($condition->operator) {
            ComparisonOperator::Equals => $expr->andX(
                $expr->gte($field, $day->bindStart($queryBuilder, $start)),
                $expr->lt($field, $day->bindNextDay($queryBuilder, $end)),
            ),
            ComparisonOperator::NotEquals => $expr->orX(
                $expr->lt($field, $day->bindStart($queryBuilder, $start)),
                $expr->gte($field, $day->bindNextDay($queryBuilder, $end)),
            ),
            ComparisonOperator::GreaterThan => $expr->gte($field, $day->bindNextDay($queryBuilder, $end)),
            ComparisonOperator::GreaterThanOrEquals => $expr->gte($field, $day->bindStart($queryBuilder, $start)),
            ComparisonOperator::LessThan => $expr->lt($field, $day->bindStart($queryBuilder, $start)),
            ComparisonOperator::LessThanOrEquals => $expr->lt($field, $day->bindNextDay($queryBuilder, $end)),
            default => null,
        };
    }

    private function createDayBetweenExpression(
        QueryBuilder $queryBuilder,
        string $field,
        string $parameterName,
        mixed $start,
        mixed $end,
        ?string $columnType,
    ): ?object {
        $firstDay = DoctrineDayRange::fromValue($start, $columnType);
        $lastDay = DoctrineDayRange::fromValue($end, $columnType);

        if (null === $firstDay || null === $lastDay) {
            return null;
        }

        $expr = $queryBuilder->expr();

        return $expr->andX(
            $expr->gte($field, $firstDay->bindStart($queryBuilder, $parameterName.'_start')),
            $expr->lt($field, $lastDay->bindNextDay($queryBuilder, $parameterName.'_end')),
        );
    }

    /**
     * The two bounds of a between value, given as a from and to map or as a list of two values.
     *
     * @return array{mixed, mixed}|null
     */
    private function betweenBounds(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        if (isset($value['from'], $value['to'])) {
            return [$value['from'], $value['to']];
        }

        $values = array_values($value);

        return 2 === count($values) ? [$values[0], $values[1]] : null;
    }

    private function createLikeExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, string $pattern): object
    {
        $queryBuilder->setParameter($parameterName, $pattern);

        return $queryBuilder->expr()->like(sprintf('LOWER(%s)', $field), sprintf('LOWER(:%s)', $parameterName));
    }

    private function createNotLikeExpression(QueryBuilder $queryBuilder, string $field, string $parameterName, string $pattern): object
    {
        $queryBuilder->setParameter($parameterName, $pattern);

        return $queryBuilder->expr()->notLike(sprintf('LOWER(%s)', $field), sprintf('LOWER(:%s)', $parameterName));
    }
}
