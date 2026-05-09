<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum FilterOperator: string
{
    case Equals = '=';
    case NotEquals = '!=';
    case GreaterThan = '>';
    case GreaterThanOrEquals = '>=';
    case LessThan = '<';
    case LessThanOrEquals = '<=';
    case In = 'IN';
    case NotIn = 'NOT IN';
    case IsNull = 'IS NULL';
    case IsNotNull = 'IS NOT NULL';
    case Between = 'BETWEEN';
    case Like = 'LIKE';
    case NotLike = 'NOT LIKE';
}
