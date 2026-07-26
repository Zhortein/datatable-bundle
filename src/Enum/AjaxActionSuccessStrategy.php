<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum AjaxActionSuccessStrategy: string
{
    case RefreshTable = 'refresh_table';
    case RefreshRow = 'refresh_row';
    case RemoveRow = 'remove_row';
    case None = 'none';
    case Redirect = 'redirect';
}
