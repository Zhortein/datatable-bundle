<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider;

use Doctrine\DBAL\Types\Types;
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
        $filteredItems = $request->hasSearchQuery()
            ? $this->countRows($entityManager, $entityClass, $definition, $request)
            : $totalItems;

        return new DatatableResult(
            rows: $rows,
            page: $request->getPage(),
            pageSize: $request->getPageSize(),
            totalItems: $totalItems,
            filteredItems: $filteredItems,
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
        $this->applySearch($queryBuilder, $entityManager, $entityClass, $definition, $request);

        /** @var list<array<string, mixed>> $rows */
        $rows = $queryBuilder->getQuery()->getArrayResult();

        return $rows;
    }

    /**
     * @param class-string $entityClass
     */
    private function countRows(
        EntityManagerInterface $entityManager,
        string $entityClass,
        DatatableDefinition $definition,
        ?DatatableRequest $request = null,
    ): int {
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select(sprintf('COUNT(%s)', self::MAIN_ALIAS))
            ->from($entityClass, self::MAIN_ALIAS)
        ;

        $this->applyPermanentFilters($queryBuilder, $definition);

        if (null !== $request) {
            $this->applySearch($queryBuilder, $entityManager, $entityClass, $definition, $request);
        }

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

    /**
     * @param class-string $entityClass
     */
    private function applySearch(
        QueryBuilder $queryBuilder,
        EntityManagerInterface $entityManager,
        string $entityClass,
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): void {
        if (!$request->hasSearchQuery()) {
            return;
        }

        $metadata = $entityManager->getClassMetadata($entityClass);

        $searchQuery = (string) $request->getSearchQuery();
        $expressions = [];
        $parameterIndex = 0;

        foreach ($definition->getColumns() as $column) {
            if (!$column->isSearchable()) {
                continue;
            }

            $fieldReference = $this->normalizeFieldReference($column->getName());
            $fieldName = $this->extractFieldName($fieldReference);

            if (!$metadata->hasField($fieldName)) {
                continue;
            }

            $doctrineType = $metadata->getTypeOfField($fieldName);

            if (null === $doctrineType) {
                continue;
            }

            $parameterName = sprintf('datatable_search_%d', $parameterIndex++);

            if ($this->isStringSearchableType($doctrineType)) {
                $expressions[] = sprintf('LOWER(%s) LIKE :%s', $fieldReference, $parameterName);
                $queryBuilder->setParameter($parameterName, '%'.mb_strtolower($searchQuery).'%');

                continue;
            }

            if ($this->isNumericSearchableType($doctrineType) && is_numeric($searchQuery)) {
                $expressions[] = sprintf('%s = :%s', $fieldReference, $parameterName);
                $queryBuilder->setParameter($parameterName, $this->normalizeNumericSearchValue($searchQuery, $doctrineType));
            }
        }

        if ([] === $expressions) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$expressions));
    }

    private function isStringSearchableType(string $doctrineType): bool
    {
        return in_array($doctrineType, [
            Types::ASCII_STRING,
            Types::STRING,
            Types::TEXT,
            Types::GUID,
        ], true);
    }

    private function isNumericSearchableType(string $doctrineType): bool
    {
        return in_array($doctrineType, [
            Types::BIGINT,
            Types::INTEGER,
            Types::SMALLINT,
        ], true);
    }

    private function normalizeNumericSearchValue(string $searchQuery, string $doctrineType): int|string
    {
        if (Types::BIGINT === $doctrineType) {
            return $searchQuery;
        }

        return (int) $searchQuery;
    }

    private function extractFieldName(string $fieldReference): string
    {
        [, $fieldName] = explode('.', $fieldReference, 2);

        return $fieldName;
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
