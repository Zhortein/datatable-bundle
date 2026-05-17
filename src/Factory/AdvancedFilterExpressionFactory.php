<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Factory;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Exception\InvalidExpressionException;
use Zhortein\DatatableBundle\Filter\Expression\AdvancedFilterExpression;
use Zhortein\DatatableBundle\Filter\Expression\ComparisonOperator;
use Zhortein\DatatableBundle\Filter\Expression\Condition;
use Zhortein\DatatableBundle\Filter\Expression\Group;
use Zhortein\DatatableBundle\Filter\Expression\LogicOperator;

final readonly class AdvancedFilterExpressionFactory
{
    /**
     * @param array<string, mixed> $payload
     */
    public function createFromArray(array $payload, ?DatatableDefinition $definition = null): ?AdvancedFilterExpression
    {
        if ([] === $payload) {
            return null;
        }

        try {
            $root = $this->parseGroup($payload, $definition);

            return new AdvancedFilterExpression($root);
        } catch (InvalidExpressionException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function parseGroup(array $payload, ?DatatableDefinition $definition): Group
    {
        $logicValue = $payload['logic'] ?? 'AND';
        $logic = is_string($logicValue) ? LogicOperator::tryFrom(strtoupper($logicValue)) : null;
        $logic ??= LogicOperator::And;

        $children = [];
        $childrenPayload = $payload['children'] ?? [];

        if (!is_array($childrenPayload) || [] === $childrenPayload) {
            throw new InvalidExpressionException('Group must have at least one child.');
        }

        foreach ($childrenPayload as $childPayload) {
            if (!is_array($childPayload)) {
                continue;
            }

            /** @var array<string, mixed> $childPayload */
            if (isset($childPayload['children'])) {
                $children[] = $this->parseGroup($childPayload, $definition);
            } else {
                $condition = $this->parseCondition($childPayload, $definition);
                if (null !== $condition) {
                    $children[] = $condition;
                }
            }
        }

        return new Group($logic, $children);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function parseCondition(array $payload, ?DatatableDefinition $definition): ?Condition
    {
        $field = $payload['field'] ?? null;
        $operatorValue = $payload['operator'] ?? null;
        $operator = is_string($operatorValue) ? ComparisonOperator::tryFrom($operatorValue) : null;
        $value = $payload['value'] ?? null;

        if (!is_string($field) || '' === trim($field) || null === $operator) {
            return null;
        }

        if (null !== $definition) {
            $advancedFilterFields = $definition->getAdvancedFilterFields();
            if (!isset($advancedFilterFields[$field])) {
                return null;
            }

            $fieldDefinition = $advancedFilterFields[$field];
            $allowedOperators = $fieldDefinition->getAllowedOperators();

            if ([] !== $allowedOperators) {
                $mappedFilterOperator = $this->mapToFilterOperator($operator);
                if (!in_array($mappedFilterOperator, $allowedOperators, true)) {
                    return null;
                }
            }
        }

        try {
            return new Condition($field, $operator, $value);
        } catch (InvalidExpressionException) {
            return null;
        }
    }

    private function mapToFilterOperator(ComparisonOperator $operator): FilterOperator
    {
        return match ($operator) {
            ComparisonOperator::Equals => FilterOperator::Equals,
            ComparisonOperator::NotEquals => FilterOperator::NotEquals,
            ComparisonOperator::Contains,
            ComparisonOperator::StartsWith,
            ComparisonOperator::EndsWith => FilterOperator::Like,
            ComparisonOperator::NotContains => FilterOperator::NotLike,
            ComparisonOperator::GreaterThan => FilterOperator::GreaterThan,
            ComparisonOperator::GreaterThanOrEquals => FilterOperator::GreaterThanOrEquals,
            ComparisonOperator::LessThan => FilterOperator::LessThan,
            ComparisonOperator::LessThanOrEquals => FilterOperator::LessThanOrEquals,
            ComparisonOperator::Between => FilterOperator::Between,
            ComparisonOperator::IsNull => FilterOperator::IsNull,
            ComparisonOperator::IsNotNull => FilterOperator::IsNotNull,
            ComparisonOperator::In => FilterOperator::In,
            ComparisonOperator::NotIn => FilterOperator::NotIn,
        };
    }
}
