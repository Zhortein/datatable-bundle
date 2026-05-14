<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\JoinType;

final readonly class CustomJoinDefinition
{
    /**
     * @param class-string $targetEntityClass
     */
    public function __construct(
        private string $alias,
        private string $targetEntityClass,
        private string $condition,
        private JoinType $type = JoinType::Left,
    ) {
        if ('' === trim($this->alias)) {
            throw new \InvalidArgumentException('The custom Doctrine join alias cannot be empty.');
        }

        if ('' === trim($this->targetEntityClass)) {
            throw new \InvalidArgumentException('The custom Doctrine join target entity class cannot be empty.');
        }

        if ('' === trim($this->condition)) {
            throw new \InvalidArgumentException('The custom Doctrine join condition cannot be empty.');
        }
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return class-string
     */
    public function getTargetEntityClass(): string
    {
        return $this->targetEntityClass;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function getType(): JoinType
    {
        return $this->type;
    }
}
