<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Filter\Expression;

final readonly class AdvancedFilterExpression
{
    public function __construct(
        public Group $root,
    ) {
    }
}
