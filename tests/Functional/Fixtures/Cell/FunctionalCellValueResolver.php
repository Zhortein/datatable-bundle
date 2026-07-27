<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Cell;

use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;

final readonly class FunctionalCellValueResolver implements CellValueResolverInterface
{
    public function getName(): string
    {
        return 'functional_cell';
    }

    public function resolve(CellContext $context): mixed
    {
        return $context->getValue();
    }
}
