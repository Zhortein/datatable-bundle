<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final readonly class DoctrinePaginationApplier
{
    public function apply(QueryBuilder $queryBuilder, DatatableRequest $request): void
    {
        if (!$request->isPaginationEnabled()) {
            return;
        }

        $queryBuilder
            ->setFirstResult($request->getOffset())
            ->setMaxResults($request->getPageSize())
        ;
    }
}
