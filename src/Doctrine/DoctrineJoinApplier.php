<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Zhortein\DatatableBundle\Definition\CustomJoinDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Definition\JoinDefinition;
use Zhortein\DatatableBundle\Enum\JoinType;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;

final readonly class DoctrineJoinApplier
{
    /**
     * @var list<string>
     */
    private const array RESERVED_ALIASES = [
        'and',
        'as',
        'asc',
        'by',
        'delete',
        'desc',
        'distinct',
        'from',
        'group',
        'having',
        'in',
        'inner',
        'is',
        'join',
        'left',
        'like',
        'new',
        'not',
        'or',
        'order',
        'select',
        'set',
        'update',
        'where',
        'with',
    ];

    public function apply(QueryBuilder $queryBuilder, DatatableDefinition $definition): void
    {
        foreach ($definition->getJoins() as $join) {
            $this->validate($join);

            match ($join->getType()) {
                JoinType::Inner => $queryBuilder->join($join->getJoin(), $join->getAlias()),
                JoinType::Left => $queryBuilder->leftJoin($join->getJoin(), $join->getAlias()),
            };
        }

        foreach ($definition->getCustomJoins() as $join) {
            $this->validateCustomJoin($join);

            match ($join->getType()) {
                JoinType::Inner => $queryBuilder->join(
                    $join->getTargetEntityClass(),
                    $join->getAlias(),
                    Join::WITH,
                    $join->getCondition(),
                ),
                JoinType::Left => $queryBuilder->leftJoin(
                    $join->getTargetEntityClass(),
                    $join->getAlias(),
                    Join::WITH,
                    $join->getCondition(),
                ),
            };
        }
    }

    public function validate(JoinDefinition $join): void
    {
        $this->validateAlias($join->getAlias(), 'Doctrine join');

        if (!str_contains($join->getJoin(), '.')) {
            throw new \InvalidArgumentException(sprintf('The Doctrine join expression "%s" must reference an association path.', $join->getJoin()));
        }
    }

    public function validateCustomJoin(CustomJoinDefinition $join): void
    {
        $this->validateAlias($join->getAlias(), 'custom Doctrine join');

        if (!class_exists($join->getTargetEntityClass())) {
            throw new \InvalidArgumentException(sprintf('The custom Doctrine join target entity class "%s" does not exist.', $join->getTargetEntityClass()));
        }

        if (!str_contains($join->getCondition(), '.')) {
            throw new \InvalidArgumentException(sprintf('The custom Doctrine join condition "%s" must reference at least one aliased field.', $join->getCondition()));
        }
    }

    private function validateAlias(string $alias, string $context): void
    {
        if (DoctrineOrmDataProvider::MAIN_ALIAS === $alias) {
            throw new \InvalidArgumentException(sprintf('The %s alias "%s" is reserved for the main entity.', $context, DoctrineOrmDataProvider::MAIN_ALIAS));
        }

        if (1 !== preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias)) {
            throw new \InvalidArgumentException(sprintf('The %s alias "%s" is invalid.', $context, $alias));
        }

        if (in_array(strtolower($alias), self::RESERVED_ALIASES, true)) {
            throw new \InvalidArgumentException(sprintf('The %s alias "%s" is reserved and cannot be used.', $context, $alias));
        }
    }
}
