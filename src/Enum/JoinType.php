<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum JoinType: string
{
    case Inner = 'inner';
    case Left = 'left';
}
