<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\FilterDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class DoctrineOrmDataProvider implements DataProviderInterface
{
    public const string PROVIDER_NAME = 'doctrine';
    public const string MAIN_ALIAS = 'e';

    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function supports(DatatableDefinition $definition): bool
    {
        return null !== $definition->getEntityClass();
    }

    public function getData(DatatableDefinition $definition, DatatableRequest $request): DatatableResult
    {
        $entityClass = $definition->getEntityClass();

        if (null === $entityClass) {
            throw new \InvalidArgumentException(sprintf('The datatable "%s" must define an entity class to use the Doctrine ORM provider.', $definition->getName()));
        }

        $entityManager = $this->getEntityManager($entityClass);
        $selectedColumns = $this->getSelectableColumns($definition);

        $rows = $this->loadRows($entityManager, $entityClass, $selectedColumns, $definition, $request);
        $totalItems = $this->countRows($entityManager, $entityClass, $definition);

        return new DatatableResult(
            rows: $rows,
            page: $request->getPage(),
            pageSize: $request->getPageSize(),
            totalItems: $totalItems,
            filteredItems: $totalItems,
        );
    }

    /**
     * @param class-string $entityClass
     */
    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClass);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException(sprintf('No Doctrine ORM entity manager found for class "%s".', $entityClass));
        }

        return $manager;
    }

    /**
     * @return array<string, ColumnDefinition>
     */
    private function getSelectableColumns(DatatableDefinition $definition): array
    {
        return array_filter(
            $definition->getColumns(),
            static fn (ColumnDefinition $column): bool => $column->isVisible(),
        );
    }

    /**
     * @param class-string                    $entityClass
     * @param array<string, ColumnDefinition> $columns
     *
     * @return list<array<string, mixed>>
     */
    private function loadRows(
        EntityManagerInterface $entityManager,
        string $entityClass,
        array $columns,
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): array {
        $queryBuilder = $entityManager->createQueryBuilder()
            ->from($entityClass, self::MAIN_ALIAS)
            ->setFirstResult($request->getOffset())
            ->setMaxResults($request->getPageSize())
        ;

        foreach ($columns as $column) {
            $fieldReference = $this->normalizeFieldReference($column->getName());
            $queryBuilder->addSelect(sprintf('%s AS %s', $fieldReference, $this->createResultAlias($fieldReference)));
        }

        if ([] === $columns) {
            $queryBuilder->select(sprintf('%s.id AS id', self::MAIN_ALIAS));
        }

        $this->applyPermanentFilters($queryBuilder, $definition);

        /** @var list<array<string, mixed>> $rows */
        $rows = $queryBuilder->getQuery()->getArrayResult();

        return $rows;
    }

    /**
     * @param class-string $entityClass
     */
    private function countRows(EntityManagerInterface $entityManager, string $entityClass, DatatableDefinition $definition): int
    {
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select(sprintf('COUNT(%s)', self::MAIN_ALIAS))
            ->from($entityClass, self::MAIN_ALIAS)
        ;

        $this->applyPermanentFilters($queryBuilder, $definition);

        $result = $queryBuilder
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    private function applyPermanentFilters(QueryBuilder $queryBuilder, DatatableDefinition $definition): void
    {
        foreach ($definition->getPermanentFilters() as $index => $filter) {
            $this->applyPermanentFilter($queryBuilder, $filter, $index);
        }
    }

    private function applyPermanentFilter(QueryBuilder $queryBuilder, FilterDefinition $filter, int $index): void
    {
        $field = $this->normalizeFieldReference($filter->getField());
        $parameterName = sprintf('permanent_filter_%d', $index);

        match ($filter->getOperator()) {
            FilterOperator::Equals => $queryBuilder
                ->andWhere(sprintf('%s = :%s', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),

            FilterOperator::NotEquals => $queryBuilder
                ->andWhere(sprintf('%s != :%s', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),

            FilterOperator::GreaterThan => $queryBuilder
                ->andWhere(sprintf('%s > :%s', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),

            FilterOperator::GreaterThanOrEquals => $queryBuilder
                ->andWhere(sprintf('%s >= :%s', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),

            FilterOperator::LessThan => $queryBuilder
                ->andWhere(sprintf('%s < :%s', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),

            FilterOperator::LessThanOrEquals => $queryBuilder
                ->andWhere(sprintf('%s <= :%s', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),

            FilterOperator::In => $queryBuilder
                ->andWhere(sprintf('%s IN (:%s)', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),

            FilterOperator::NotIn => $queryBuilder
                ->andWhere(sprintf('%s NOT IN (:%s)', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),

            FilterOperator::IsNull => $queryBuilder
                ->andWhere(sprintf('%s IS NULL', $field)),

            FilterOperator::IsNotNull => $queryBuilder
                ->andWhere(sprintf('%s IS NOT NULL', $field)),

            FilterOperator::Between => $queryBuilder
                ->andWhere(sprintf('%s BETWEEN :%s_start AND :%s_end', $field, $parameterName, $parameterName))
                ->setParameter(sprintf('%s_start', $parameterName), $filter->getValue())
                ->setParameter(sprintf('%s_end', $parameterName), $filter->getSecondValue()),

            FilterOperator::Like => $queryBuilder
                ->andWhere(sprintf('%s LIKE :%s', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),

            FilterOperator::NotLike => $queryBuilder
                ->andWhere(sprintf('%s NOT LIKE :%s', $field, $parameterName))
                ->setParameter($parameterName, $filter->getValue()),
        };
    }

    private function normalizeFieldReference(string $columnName): string
    {
        if (!str_contains($columnName, '.')) {
            return self::MAIN_ALIAS.'.'.$columnName;
        }

        [$alias] = explode('.', $columnName, 2);

        if (self::MAIN_ALIAS !== $alias) {
            throw new \InvalidArgumentException(sprintf('Only the main Doctrine alias "%s" is supported by the initial Doctrine ORM provider skeleton. Got "%s".', self::MAIN_ALIAS, $alias));
        }

        return $columnName;
    }

    private function createResultAlias(string $fieldReference): string
    {
        return str_replace('.', '_', $fieldReference);
    }
}
