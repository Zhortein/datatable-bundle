<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Cell;

use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;
use Zhortein\DatatableBundle\Exception\CellValueResolverNotFoundException;
use Zhortein\DatatableBundle\Exception\DuplicateCellValueResolverException;

final readonly class CellValueResolverRegistry
{
    public const string SERVICE_TAG = 'zhortein_datatable.cell_value_resolver';

    /**
     * @var array<string, CellValueResolverInterface>
     */
    private array $resolvers;

    /**
     * @param iterable<CellValueResolverInterface> $resolvers
     */
    public function __construct(iterable $resolvers = [])
    {
        $normalizedResolvers = [];

        foreach ($resolvers as $resolver) {
            $name = trim($resolver->getName());

            if ('' === $name) {
                throw new CellValueResolverNotFoundException('A cell value resolver cannot be registered with an empty name.');
            }

            if (isset($normalizedResolvers[$name])) {
                throw new DuplicateCellValueResolverException(sprintf('A cell value resolver named "%s" is already registered.', $name));
            }

            $normalizedResolvers[$name] = $resolver;
        }

        $this->resolvers = $normalizedResolvers;
    }

    public function has(string $name): bool
    {
        return isset($this->resolvers[$name]);
    }

    public function get(string $name): CellValueResolverInterface
    {
        if (!$this->has($name)) {
            throw new CellValueResolverNotFoundException(sprintf('The cell value resolver "%s" is not registered.', $name));
        }

        return $this->resolvers[$name];
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->resolvers);
    }
}
