<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum HttpPaginationStrategy: string
{
    case Page = 'page';
    case Offset = 'offset';
    case Cursor = 'cursor';
}
