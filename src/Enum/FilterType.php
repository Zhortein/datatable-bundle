<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum FilterType: string
{
    case Text = 'text';
    case Choice = 'choice';
    case Boolean = 'boolean';
    case Date = 'date';
    case DateRange = 'date_range';
    case Number = 'number';
    case NumberRange = 'number_range';
}
