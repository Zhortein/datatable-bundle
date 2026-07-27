<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Cell;

use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final readonly class CellContextFactory
{
    private CellValueResolverRegistry $valueResolverRegistry;

    public function __construct(?CellValueResolverRegistry $valueResolverRegistry = null)
    {
        $this->valueResolverRegistry = $valueResolverRegistry ?? new CellValueResolverRegistry();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function create(
        DatatableDefinition $definition,
        ColumnDefinition $column,
        array $row,
        mixed $source = null,
    ): CellContext {
        $context = new CellContext(
            value: $column->isComputed() ? null : $this->readColumnValue($row, $column),
            row: $row,
            source: $source,
            rowIdentifier: $this->resolveRowIdentifier($row, $definition),
            column: $column,
            definition: $definition,
            datatableContext: $definition->getContext(),
        );

        $resolverName = $column->getValueResolver();

        if (null === $resolverName) {
            return $context;
        }

        return $context->withValue(
            $this->valueResolverRegistry->get($resolverName)->resolve($context),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    public function resolveRowIdentifier(array $row, DatatableDefinition $definition): ?string
    {
        $identifierKey = $definition->getOption('identifier');

        if (is_string($identifierKey) && '' !== trim($identifierKey)) {
            return $this->normalizeIdentifier($this->readValue($row, trim($identifierKey)));
        }

        foreach (['id', 'e_id'] as $candidate) {
            if (array_key_exists($candidate, $row)) {
                return $this->normalizeIdentifier($row[$candidate]);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readColumnValue(array $row, ColumnDefinition $column): mixed
    {
        return $this->readValue($row, $column->getName());
    }

    /**
     * @param array<string, mixed> $row
     */
    private function readValue(array $row, string $name): mixed
    {
        foreach ($this->getValueCandidateKeys($name) as $candidateKey) {
            if (array_key_exists($candidateKey, $row)) {
                return $row[$candidateKey];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getValueCandidateKeys(string $name): array
    {
        $candidateKeys = [$name];

        if (str_contains($name, '.')) {
            $candidateKeys[] = str_replace('.', '_', $name);

            $parts = explode('.', $name);
            $lastPart = $parts[array_key_last($parts)];

            if ('' !== $lastPart) {
                $candidateKeys[] = $lastPart;
            }
        }

        return array_values(array_unique($candidateKeys));
    }

    private function normalizeIdentifier(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
