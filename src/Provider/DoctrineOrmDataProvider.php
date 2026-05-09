<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Zhortein\DatatableBundle\Contract\DataProviderInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
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

        $rows = $this->loadRows($entityManager, $entityClass, $selectedColumns, $request);
        $totalItems = $this->countRows($entityManager, $entityClass);

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

        /** @var list<array<string, mixed>> $rows */
        $rows = $queryBuilder->getQuery()->getArrayResult();

        return $rows;
    }

    /**
     * @param class-string $entityClass
     */
    private function countRows(EntityManagerInterface $entityManager, string $entityClass): int
    {
        $result = $entityManager->createQueryBuilder()
            ->select(sprintf('COUNT(%s)', self::MAIN_ALIAS))
            ->from($entityClass, self::MAIN_ALIAS)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
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
