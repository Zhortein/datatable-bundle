<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ThemeAssetType: string
{
    case JavaScript = 'javascript';
    case Stylesheet = 'stylesheet';
}
