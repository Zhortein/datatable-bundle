<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Filter\Expression;

interface ExpressionInterface
{
    public function getDepth(): int;
}
