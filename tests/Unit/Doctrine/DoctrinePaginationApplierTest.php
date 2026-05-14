<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Doctrine\DoctrinePaginationApplier;
use Zhortein\DatatableBundle\Request\DatatableRequest;

#[AllowMockObjectsWithoutExpectations]
final class DoctrinePaginationApplierTest extends TestCase
{
    public function test_it_applies_pagination_when_enabled(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        new DoctrinePaginationApplier()->apply(
            $queryBuilder,
            DatatableRequest::create(page: 3, pageSize: 25),
        );

        self::assertSame(50, $queryBuilder->getFirstResult());
        self::assertSame(25, $queryBuilder->getMaxResults());
    }

    public function test_it_does_not_apply_pagination_when_disabled(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        new DoctrinePaginationApplier()->apply(
            $queryBuilder,
            DatatableRequest::create(page: 3, pageSize: 25)->withoutPagination(),
        );

        self::assertSame(0, $queryBuilder->getFirstResult());
        self::assertNull($queryBuilder->getMaxResults());
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        return new QueryBuilder($entityManager);
    }
}
