<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Filter\Expression;

enum LogicOperator: string
{
    case And = 'AND';
    case Or = 'OR';
}
