<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ChildContextSource: string
{
    case Row = 'row';
    case Context = 'context';
    case Literal = 'literal';
}
