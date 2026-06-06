<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Filter\Expression;

use Zhortein\DatatableBundle\Exception\InvalidExpressionException;

final readonly class Group implements ExpressionInterface
{
    private const int MAX_DEPTH = 3;

    /**
     * @param ExpressionInterface[] $children
     */
    public function __construct(
        public LogicOperator $logic = LogicOperator::And,
        public array $children = [],
    ) {
        if ([] === $children) {
            throw new InvalidExpressionException('Group must have at least one child.');
        }

        foreach ($children as $child) {
            /** @phpstan-ignore-next-line */
            if (!$child instanceof ExpressionInterface) {
                throw new InvalidExpressionException('All children of a Group must implement ExpressionInterface.');
            }
        }

        if ($this->getDepth() > self::MAX_DEPTH) {
            throw new InvalidExpressionException(sprintf('Expression tree depth exceeds maximum allowed depth of %d.', self::MAX_DEPTH));
        }
    }

    public function getDepth(): int
    {
        $maxChildDepth = 0;
        foreach ($this->children as $child) {
            $maxChildDepth = max($maxChildDepth, $child->getDepth());
        }

        return 1 + $maxChildDepth;
    }
}
