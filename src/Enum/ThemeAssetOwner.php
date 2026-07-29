<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum ThemeAssetOwner: string
{
    case Bundle = 'bundle';
    case HostApplication = 'host_application';
    case ThemePackage = 'theme_package';
}
