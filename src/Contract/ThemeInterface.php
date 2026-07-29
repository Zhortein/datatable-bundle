<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\Enum\CellType;
use Zhortein\DatatableBundle\Theme\ThemeMetadata;

interface ThemeInterface
{
    public function getMetadata(): ThemeMetadata;

    public function getDefaultCellClassName(CellType $cellType): ?string;
}
