<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum RouteParameterSource: string
{
    case Row = 'row';
    case Literal = 'literal';
    case Context = 'context';
}
