<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\JoinDefinition;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;

final readonly class DoctrineJoinApplier
{
    public function apply(QueryBuilder $queryBuilder, DatatableDefinition $definition): void
    {
        foreach ($definition->getJoins() as $join) {
            $this->validate($join);

            match ($join->getType()) {
                JoinType::Inner => $queryBuilder->join($join->getJoin(), $join->getAlias()),
                JoinType::Left => $queryBuilder->leftJoin($join->getJoin(), $join->getAlias()),
            };
        }
    }

    public function validate(JoinDefinition $join): void
    {
        if (DoctrineOrmDataProvider::MAIN_ALIAS === $join->getAlias()) {
            throw new \InvalidArgumentException(sprintf('The Doctrine join alias "%s" is reserved for the main entity.', DoctrineOrmDataProvider::MAIN_ALIAS));
        }

        if (1 !== preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $join->getAlias())) {
            throw new \InvalidArgumentException(sprintf('The Doctrine join alias "%s" is invalid.', $join->getAlias()));
        }

        if (!str_contains($join->getJoin(), '.')) {
            throw new \InvalidArgumentException(sprintf('The Doctrine join expression "%s" must reference an association path.', $join->getJoin()));
        }
    }
}
