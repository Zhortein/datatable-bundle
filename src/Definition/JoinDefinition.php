<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

use Zhortein\DatatableBundle\Enum\JoinType;

final readonly class JoinDefinition
{
    public function __construct(
        private string $alias,
        private string $join,
        private JoinType $type = JoinType::Left,
    ) {
        if ('' === trim($this->alias)) {
            throw new \InvalidArgumentException('The datatable join alias cannot be empty.');
        }

        if ('' === trim($this->join)) {
            throw new \InvalidArgumentException('The datatable join expression cannot be empty.');
        }
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getJoin(): string
    {
        return $this->join;
    }

    public function getType(): JoinType
    {
        return $this->type;
    }
}
