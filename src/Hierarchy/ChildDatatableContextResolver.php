<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Hierarchy;

use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\ChildContextValue;
use Zhortein\DatatableBundle\Definition\ChildDatatableDefinition;
use Zhortein\DatatableBundle\Enum\ChildContextSource;
use Zhortein\DatatableBundle\Exception\InvalidChildDatatableContextValueException;
use Zhortein\DatatableBundle\Exception\MissingChildDatatableContextValueException;

/**
 * @internal
 */
final readonly class ChildDatatableContextResolver
{
    public function __construct(
        private RowValueAccessor $rowValueAccessor,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function resolve(
        ChildDatatableDefinition $definition,
        array $row,
        DatatableContext $parentContext,
    ): DatatableContext {
        $values = [];

        foreach ($definition->getContext() as $name => $mapping) {
            [$found, $value, $missingReason] = $this->readValue($mapping, $row, $parentContext);

            if (ChildContextSource::Literal !== $mapping->getSource() && (!$found || null === $value)) {
                if ($mapping->hasDefault()) {
                    $value = $mapping->getDefaultValue();

                    if (null === $value) {
                        continue;
                    }
                } elseif (!$mapping->isRequired()) {
                    continue;
                } else {
                    throw new MissingChildDatatableContextValueException(sprintf('Unable to resolve required context value "%s" for child datatable "%s" from %s: %s.', $name, $definition->getName(), $this->describeSource($mapping), $missingReason));
                }
            }

            $values[$name] = $this->normalizeValue(
                value: $value,
                definition: $definition,
                name: $name,
                sourceDescription: $this->describeSource($mapping),
            );
        }

        return new DatatableContext($values, array_keys($values));
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{bool, mixed, string}
     */
    private function readValue(
        ChildContextValue $mapping,
        array $row,
        DatatableContext $parentContext,
    ): array {
        if (ChildContextSource::Literal === $mapping->getSource()) {
            return [true, $mapping->getValue(), 'the literal value is null'];
        }

        $key = $mapping->getKey();

        if (null === $key) {
            throw new \LogicException(sprintf('The %s child context source requires a key.', $mapping->getSource()->value));
        }

        if (ChildContextSource::Context === $mapping->getSource()) {
            if (!$parentContext->has($key)) {
                return [false, null, 'the parent context key is missing'];
            }

            $value = $parentContext->get($key);

            return [true, $value, null === $value ? 'the parent context value is null' : ''];
        }

        [$found, $value] = $this->rowValueAccessor->read($row, $key);

        return [$found, $value, $found ? 'the parent row value is null' : 'the parent row value is missing'];
    }

    private function describeSource(ChildContextValue $mapping): string
    {
        if (ChildContextSource::Literal === $mapping->getSource()) {
            return 'literal source';
        }

        return sprintf('%s source "%s"', $mapping->getSource()->value, $mapping->getKey());
    }

    private function normalizeValue(
        mixed $value,
        ChildDatatableDefinition $definition,
        string $name,
        string $sourceDescription,
    ): bool|float|int|string|null {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (null === $value || is_scalar($value)) {
            return $value;
        }

        throw new InvalidChildDatatableContextValueException(sprintf('Unable to resolve context value "%s" for child datatable "%s" from %s: values of type "%s" are not transportable.', $name, $definition->getName(), $sourceDescription, get_debug_type($value)));
    }
}
