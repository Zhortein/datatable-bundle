<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\ExpressionInterface;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;

final class DoctrineExpressionApplier
{
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
        try {
            $reference = $this->fieldReferenceResolver->normalize($condition->field, $definition);
        } catch (\InvalidArgumentException) {
            return null;
        }

        if (!$this->fieldMetadataResolver->hasField($entityManager, $entityClass, $definition, $reference)) {
            return null;
        }

        $field = $reference->toString();
        $parameterName = sprintf('advanced_filter_%d', $this->parameterIndex++);

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
        if (!is_array($value) || 2 !== count($value)) {
            return null;
        }

        $startParam = $parameterName.'_start';
        $endParam = $parameterName.'_end';

        $queryBuilder->setParameter($startParam, $value[0]);
        $queryBuilder->setParameter($endParam, $value[1]);

        return $queryBuilder->expr()->between($field, ':'.$startParam, ':'.$endParam);
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
