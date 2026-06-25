<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Definition\AggregateColumnDefinition;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\FilterDefinition;
use Zhortein\DatatableBundle\Definition\UserFilterDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineCountExpressionFactory;
use Zhortein\DatatableBundle\Doctrine\DoctrineExpressionApplier;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldMetadataResolver;
use Zhortein\DatatableBundle\Doctrine\DoctrineFieldReferenceResolver;
use Zhortein\DatatableBundle\Doctrine\DoctrineJoinApplier;
use Zhortein\DatatableBundle\Doctrine\DoctrinePaginationApplier;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Enum\FilterType;
use Zhortein\DatatableBundle\Request\DatatableRequest;
use Zhortein\DatatableBundle\Result\DatatableResult;

final readonly class DoctrineOrmDataProvider implements DataProviderInterface
{
    public const string PROVIDER_NAME = 'doctrine';
    public const string MAIN_ALIAS = 'e';

    private DoctrineFieldReferenceResolver $fieldReferenceResolver;

    private DoctrineJoinApplier $joinApplier;

    private DoctrinePaginationApplier $paginationApplier;

    private DoctrineFieldMetadataResolver $fieldMetadataResolver;

    private DoctrineCountExpressionFactory $countExpressionFactory;

    private DoctrineExpressionApplier $expressionApplier;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        ?DoctrineFieldReferenceResolver $fieldReferenceResolver = null,
        ?DoctrineFieldMetadataResolver $fieldMetadataResolver = null,
        ?DoctrineJoinApplier $joinApplier = null,
        ?DoctrinePaginationApplier $paginationApplier = null,
        ?DoctrineCountExpressionFactory $countExpressionFactory = null,
        ?DoctrineExpressionApplier $expressionApplier = null,
    ) {
        $this->fieldReferenceResolver = $fieldReferenceResolver ?? new DoctrineFieldReferenceResolver();
        $this->fieldMetadataResolver = $fieldMetadataResolver ?? new DoctrineFieldMetadataResolver();
        $this->joinApplier = $joinApplier ?? new DoctrineJoinApplier();
        $this->paginationApplier = $paginationApplier ?? new DoctrinePaginationApplier();
        $this->countExpressionFactory = $countExpressionFactory ?? new DoctrineCountExpressionFactory();
        $this->expressionApplier = $expressionApplier ?? new DoctrineExpressionApplier(
            fieldReferenceResolver: $this->fieldReferenceResolver,
            fieldMetadataResolver: $this->fieldMetadataResolver,
        );
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
        $filteredItems = $request->hasSearchQuery() || $request->hasFilters() || $request->hasAdvancedFilters()
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
            static fn (ColumnDefinition $column): bool => true /* $column->isVisible() */,
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
        ;

        $this->paginationApplier->apply($queryBuilder, $request);

        $this->joinApplier->apply($queryBuilder, $definition);
        $this->bindCustomJoinParameters($queryBuilder, $definition);

        $aggregateColumns = $definition->getAggregateColumns();
        $hasAggregateColumns = [] !== $aggregateColumns;
        $groupByFields = [];

        foreach ($columns as $column) {
            $aggregateColumn = $aggregateColumns[$column->getName()] ?? null;

            if ($aggregateColumn instanceof AggregateColumnDefinition) {
                $queryBuilder->addSelect($this->createAggregateSelect($definition, $aggregateColumn));
                continue;
            }

            $fieldReference = $this->fieldReferenceResolver->normalize($column->getName(), $definition);

            $queryBuilder->addSelect(sprintf(
                '%s AS %s',
                $fieldReference->toString(),
                $fieldReference->toResultAlias(),
            ));

            if ($hasAggregateColumns) {
                $groupByFields[] = $fieldReference->toString();
            }
        }

        if ([] === $columns) {
            $queryBuilder->select(sprintf('%s.id AS id', self::MAIN_ALIAS));
        }

        $this->applyPermanentFilters($queryBuilder, $definition);
        $this->applyUserFilters($queryBuilder, $entityManager, $entityClass, $definition, $request);
        $this->applyAdvancedFilters($queryBuilder, $entityManager, $entityClass, $definition, $request);
        $this->applySearch($queryBuilder, $entityManager, $entityClass, $definition, $request);
        $this->applySorting($queryBuilder, $entityManager, $entityClass, $definition, $request);

        foreach (array_values(array_unique($groupByFields)) as $groupByField) {
            $queryBuilder->addGroupBy($groupByField);
        }

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
        $metadata = $entityManager->getClassMetadata($entityClass);

        $queryBuilder = $entityManager->createQueryBuilder()
            ->select($this->countExpressionFactory->create($definition, $metadata))
            ->from($entityClass, self::MAIN_ALIAS)
        ;

        $this->joinApplier->apply($queryBuilder, $definition);
        $this->bindCustomJoinParameters($queryBuilder, $definition);

        $this->applyPermanentFilters($queryBuilder, $definition);

        if (null !== $request) {
            $this->applyUserFilters($queryBuilder, $entityManager, $entityClass, $definition, $request);
            $this->applyAdvancedFilters($queryBuilder, $entityManager, $entityClass, $definition, $request);
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
            $this->applyPermanentFilter($queryBuilder, $filter, $index, $definition);
        }
    }

    private function applyPermanentFilter(
        QueryBuilder $queryBuilder,
        FilterDefinition $filter,
        int $index,
        DatatableDefinition $definition,
    ): void {
        $field = $this->fieldReferenceResolver
            ->normalize($filter->getField(), $definition)
            ->toString();
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
    private function applyUserFilters(
        QueryBuilder $queryBuilder,
        EntityManagerInterface $entityManager,
        string $entityClass,
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): void {
        if (!$request->hasFilters()) {
            return;
        }

        foreach ($definition->getFilters() as $filter) {
            if (!$request->hasFilter($filter->getName())) {
                continue;
            }

            $filterValue = $request->getFilter($filter->getName());

            if (null === $filterValue) {
                continue;
            }

            $this->applyUserFilter(
                queryBuilder: $queryBuilder,
                entityManager: $entityManager,
                entityClass: $entityClass,
                definition: $definition,
                filter: $filter,
                value: $filterValue,
            );
        }
    }

    /**
     * @param class-string $entityClass
     */
    private function applyAdvancedFilters(
        QueryBuilder $queryBuilder,
        EntityManagerInterface $entityManager,
        string $entityClass,
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): void {
        if (!$request->hasAdvancedFilters()) {
            return;
        }

        $expression = $request->getAdvancedFilterExpression();

        if (null === $expression) {
            return;
        }

        $this->expressionApplier->apply(
            queryBuilder: $queryBuilder,
            expression: $expression,
            definition: $definition,
            entityManager: $entityManager,
            entityClass: $entityClass,
        );
    }

    /**
     * @param class-string $entityClass
     */
    private function applyUserFilter(
        QueryBuilder $queryBuilder,
        EntityManagerInterface $entityManager,
        string $entityClass,
        DatatableDefinition $definition,
        UserFilterDefinition $filter,
        mixed $value,
    ): void {
        $reference = $this->fieldReferenceResolver->normalize($filter->getField(), $definition);

        $fieldReference = $reference->toString();
        if (!$this->fieldMetadataResolver->hasField($entityManager, $entityClass, $definition, $reference)) {
            return;
        }

        $parameterName = sprintf('datatable_filter_%s', preg_replace('/[^a-zA-Z0-9_]+/', '_', $filter->getName()));

        match ($filter->getType()) {
            FilterType::Text => $this->applyTextUserFilter($queryBuilder, $fieldReference, $parameterName, $value),
            FilterType::Choice, FilterType::Enum => $this->applyChoiceUserFilter($queryBuilder, $fieldReference, $parameterName, $value),
            FilterType::Boolean => $this->applyBooleanUserFilter($queryBuilder, $fieldReference, $parameterName, $value),
            FilterType::Date => $this->applyDateUserFilter($queryBuilder, $fieldReference, $parameterName, $value),
            FilterType::DateRange => $this->applyRangeUserFilter($queryBuilder, $fieldReference, $parameterName, $value),
            FilterType::Number => $this->applyNumberUserFilter($queryBuilder, $fieldReference, $parameterName, $value),
            FilterType::NumberRange => $this->applyRangeUserFilter($queryBuilder, $fieldReference, $parameterName, $value),
        };
    }

    private function applyTextUserFilter(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): void
    {
        if (!is_scalar($value)) {
            return;
        }

        $queryBuilder
            ->andWhere(sprintf('LOWER(%s) LIKE :%s', $field, $parameterName))
            ->setParameter($parameterName, '%'.mb_strtolower((string) $value).'%')
        ;
    }

    private function applyChoiceUserFilter(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): void
    {
        if (is_array($value)) {
            $queryBuilder
                ->andWhere(sprintf('%s IN (:%s)', $field, $parameterName))
                ->setParameter($parameterName, $value)
            ;

            return;
        }

        if (!is_scalar($value)) {
            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s = :%s', $field, $parameterName))
            ->setParameter($parameterName, $value)
        ;
    }

    private function applyBooleanUserFilter(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): void
    {
        $normalizedValue = $this->normalizeBooleanFilterValue($value);

        if (null === $normalizedValue) {
            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s = :%s', $field, $parameterName))
            ->setParameter($parameterName, $normalizedValue)
        ;
    }

    private function applyDateUserFilter(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): void
    {
        if (!is_scalar($value)) {
            return;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $value);

        if (!$date instanceof \DateTimeImmutable) {
            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s >= :%s_start', $field, $parameterName))
            ->andWhere(sprintf('%s < :%s_end', $field, $parameterName))
            ->setParameter(sprintf('%s_start', $parameterName), $date->setTime(0, 0))
            ->setParameter(sprintf('%s_end', $parameterName), $date->modify('+1 day')->setTime(0, 0))
        ;
    }

    private function applyNumberUserFilter(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): void
    {
        if (!is_numeric($value)) {
            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s = :%s', $field, $parameterName))
            ->setParameter($parameterName, $value)
        ;
    }

    private function applyRangeUserFilter(QueryBuilder $queryBuilder, string $field, string $parameterName, mixed $value): void
    {
        if (!is_array($value)) {
            return;
        }

        $from = $value['from'] ?? null;
        $to = $value['to'] ?? null;

        if (is_scalar($from)) {
            $queryBuilder
                ->andWhere(sprintf('%s >= :%s_from', $field, $parameterName))
                ->setParameter(sprintf('%s_from', $parameterName), $from)
            ;
        }

        if (is_scalar($to)) {
            $queryBuilder
                ->andWhere(sprintf('%s <= :%s_to', $field, $parameterName))
                ->setParameter(sprintf('%s_to', $parameterName), $to)
            ;
        }
    }

    private function normalizeBooleanFilterValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return 1 === $value ? true : (0 === $value ? false : null);
        }

        if (!is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
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

        $searchQuery = (string) $request->getSearchQuery();
        $expressions = [];
        $parameterIndex = 0;

        foreach ($definition->getColumns() as $column) {
            if (!$column->isSearchable()) {
                continue;
            }

            $reference = $this->fieldReferenceResolver->normalize($column->getName(), $definition);

            $fieldReference = $reference->toString();
            $doctrineType = $this->fieldMetadataResolver->getTypeOfField(
                entityManager: $entityManager,
                mainEntityClass: $entityClass,
                definition: $definition,
                reference: $reference,
            );

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

    /**
     * @param class-string $entityClass
     */
    private function applySorting(
        QueryBuilder $queryBuilder,
        EntityManagerInterface $entityManager,
        string $entityClass,
        DatatableDefinition $definition,
        DatatableRequest $request,
    ): void {
        if (!$request->hasSort()) {
            return;
        }

        $sortField = $request->getSortField();

        if (null === $sortField) {
            return;
        }

        $column = $definition->getColumns()[$sortField] ?? null;

        if (!$column instanceof ColumnDefinition || !$column->isSortable()) {
            return;
        }

        $reference = $this->fieldReferenceResolver->normalize($column->getName(), $definition);

        $fieldReference = $reference->toString();
        if (!$this->fieldMetadataResolver->hasField($entityManager, $entityClass, $definition, $reference)) {
            return;
        }

        $queryBuilder->addOrderBy($fieldReference, strtoupper($request->getSortDirection()->value));
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

    private function bindCustomJoinParameters(QueryBuilder $queryBuilder, DatatableDefinition $definition): void
    {
        $parameters = $definition->getOption('customJoinParameters', []);

        if (!is_array($parameters)) {
            return;
        }

        foreach ($parameters as $name => $value) {
            if (!is_string($name) || '' === trim($name)) {
                continue;
            }

            $queryBuilder->setParameter($name, $value);
        }
    }

    private function createAggregateSelect(
        DatatableDefinition $definition,
        AggregateColumnDefinition $aggregateColumn,
    ): string {
        $fieldReference = $this->fieldReferenceResolver
            ->normalize($aggregateColumn->getField(), $definition)
            ->toString();

        $expression = sprintf(
            '%s(%s%s)',
            $aggregateColumn->getFunction()->getDqlFunction(),
            $aggregateColumn->isDistinct() ? 'DISTINCT ' : '',
            $fieldReference,
        );

        return sprintf('%s AS %s', $expression, $aggregateColumn->getName());
    }
}
