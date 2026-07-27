<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum DatatableViewOperation: string
{
    case List = 'list';
    case Create = 'create';
    case Load = 'load';
    case Rename = 'rename';
    case Update = 'update';
    case SetDefault = 'set_default';
    case Delete = 'delete';
}
