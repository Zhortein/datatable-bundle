<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Filter\Expression;

enum ComparisonOperator: string
{
    case Equals = 'eq';
    case NotEquals = 'neq';
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case StartsWith = 'starts_with';
    case EndsWith = 'ends_with';
    case GreaterThan = 'gt';
    case GreaterThanOrEquals = 'gte';
    case LessThan = 'lt';
    case LessThanOrEquals = 'lte';
    case Between = 'between';
    case IsNull = 'is_null';
    case IsNotNull = 'is_not_null';
    case In = 'in';
    case NotIn = 'not_in';
}
