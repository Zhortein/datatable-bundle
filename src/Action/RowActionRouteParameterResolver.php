<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Action;

use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Definition\BulkActionDefinition;
use Zhortein\DatatableBundle\Definition\RouteParameter;
use Zhortein\DatatableBundle\Enum\RouteParameterSource;
use Zhortein\DatatableBundle\Exception\InvalidRouteParameterValueException;
use Zhortein\DatatableBundle\Exception\MissingRouteParameterValueException;

final readonly class RowActionRouteParameterResolver
{
    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    public function resolve(ActionDefinition $action, array $row, ?DatatableContext $context = null): array
    {
        return $this->resolveParameters(
            action: $action,
            context: $context ?? new DatatableContext(),
            actionType: 'row action',
            row: $row,
            legacyStringsReadRow: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveGlobalAction(ActionDefinition $action, DatatableContext $context): array
    {
        return $this->resolveParameters(
            action: $action,
            context: $context,
            actionType: 'global action',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveBulkAction(BulkActionDefinition $action, DatatableContext $context): array
    {
        return $this->resolveParameters(
            action: $action,
            context: $context,
            actionType: 'bulk action',
        );
    }

    /**
     * @param array<string, mixed>|null $row
     *
     * @return array<string, mixed>
     */
    private function resolveParameters(
        ActionDefinition|BulkActionDefinition $action,
        DatatableContext $context,
        string $actionType,
        ?array $row = null,
        bool $legacyStringsReadRow = false,
    ): array {
        $resolvedParameters = [];

        foreach ($action->getRouteParameters() as $routeParameterName => $parameter) {
            if (is_string($parameter)) {
                $value = $legacyStringsReadRow
                    ? $this->resolveLegacyRowValue($action, $row ?? [], $routeParameterName, $parameter)
                    : $parameter;

                $resolvedParameters[$routeParameterName] = $this->normalizeRouteValue(
                    value: $value,
                    action: $action,
                    actionType: $actionType,
                    routeParameterName: $routeParameterName,
                    sourceDescription: $legacyStringsReadRow
                        ? sprintf('legacy row source "%s"', $parameter)
                        : 'legacy literal source',
                );

                continue;
            }

            [$found, $value, $missingReason] = $this->readTypedValue($parameter, $row, $context);

            if (RouteParameterSource::Literal !== $parameter->getSource() && (!$found || null === $value)) {
                if ($parameter->hasDefault()) {
                    $value = $parameter->getDefaultValue();

                    if (null === $value) {
                        continue;
                    }
                } elseif (!$parameter->isRequired()) {
                    continue;
                } else {
                    throw new MissingRouteParameterValueException(sprintf(
                        'Unable to resolve required route parameter "%s" for %s "%s" from %s: %s.',
                        $routeParameterName,
                        $actionType,
                        $action->getName(),
                        $this->describeSource($parameter),
                        $missingReason,
                    ));
                }
            }

            $resolvedParameters[$routeParameterName] = $this->normalizeRouteValue(
                value: $value,
                action: $action,
                actionType: $actionType,
                routeParameterName: $routeParameterName,
                sourceDescription: $this->describeSource($parameter),
            );
        }

        return $resolvedParameters;
    }

    /**
     * @param array<string, mixed>|null $row
     *
     * @return array{bool, mixed, string}
     */
    private function readTypedValue(RouteParameter $parameter, ?array $row, DatatableContext $context): array
    {
        if (RouteParameterSource::Literal === $parameter->getSource()) {
            return [true, $parameter->getValue(), 'the literal value is null'];
        }

        $key = $parameter->getKey();

        if (null === $key) {
            throw new \LogicException(sprintf('The %s route parameter source requires a key.', $parameter->getSource()->value));
        }

        if (RouteParameterSource::Context === $parameter->getSource()) {
            if (!$context->has($key)) {
                return [false, null, 'the context key is not allowlisted'];
            }

            $value = $context->get($key);

            return [true, $value, null === $value ? 'the context value is null' : ''];
        }

        if (null === $row) {
            return [false, null, 'no row is available'];
        }

        [$found, $value] = $this->readTypedRowValue($row, $key);

        return [$found, $value, $found ? 'the row value is null' : 'the row value is missing'];
    }

    private function describeSource(RouteParameter $parameter): string
    {
        if (RouteParameterSource::Literal === $parameter->getSource()) {
            return 'literal source';
        }

        return sprintf('%s source "%s"', $parameter->getSource()->value, $parameter->getKey());
    }

    private function normalizeRouteValue(
        mixed $value,
        ActionDefinition|BulkActionDefinition $action,
        string $actionType,
        string $routeParameterName,
        string $sourceDescription,
    ): mixed {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (null === $value || is_scalar($value)) {
            return $value;
        }

        throw new InvalidRouteParameterValueException(sprintf(
            'Unable to resolve route parameter "%s" for %s "%s" from %s: values of type "%s" are not supported by the action route contract.',
            $routeParameterName,
            $actionType,
            $action->getName(),
            $sourceDescription,
            get_debug_type($value),
        ));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveLegacyRowValue(
        ActionDefinition|BulkActionDefinition $action,
        array $row,
        string $routeParameterName,
        string $rowKey,
    ): mixed {
        foreach ($this->getLegacyCandidateKeys($rowKey) as $candidateKey) {
            if (array_key_exists($candidateKey, $row)) {
                return $row[$candidateKey];
            }
        }

        throw new MissingRouteParameterValueException(sprintf('Unable to resolve route parameter "%s" for row action "%s" from row key "%s".', $routeParameterName, $action->getName(), $rowKey));
    }

    /**
     * @return list<string>
     */
    private function getLegacyCandidateKeys(string $rowKey): array
    {
        $candidateKeys = [$rowKey];

        if (str_contains($rowKey, '.')) {
            $candidateKeys[] = str_replace('.', '_', $rowKey);

            $parts = explode('.', $rowKey);
            $lastPart = $parts[array_key_last($parts)];

            if ('' !== $lastPart) {
                $candidateKeys[] = $lastPart;
            }
        }

        return array_values(array_unique($candidateKeys));
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{bool, mixed}
     */
    private function readTypedRowValue(array $row, string $path): array
    {
        if (array_key_exists($path, $row)) {
            return [true, $row[$path]];
        }

        if (!str_contains($path, '.')) {
            return [false, null];
        }

        $normalizedAlias = str_replace('.', '_', $path);

        if (array_key_exists($normalizedAlias, $row)) {
            return [true, $row[$normalizedAlias]];
        }

        [$found, $value] = $this->readNestedPath($row, $path);

        if ($found) {
            return [true, $value];
        }

        $parts = explode('.', $path);
        $lastPart = $parts[array_key_last($parts)];

        if ('' !== $lastPart && array_key_exists($lastPart, $row)) {
            return [true, $row[$lastPart]];
        }

        return [false, null];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{bool, mixed}
     */
    private function readNestedPath(array $row, string $path): array
    {
        $value = $row;

        foreach (explode('.', $path) as $segment) {
            [$found, $value] = $this->readPathSegment($value, $segment);

            if (!$found) {
                return [false, null];
            }
        }

        return [true, $value];
    }

    /**
     * @return array{bool, mixed}
     */
    private function readPathSegment(mixed $value, string $segment): array
    {
        if (is_array($value)) {
            return array_key_exists($segment, $value)
                ? [true, $value[$segment]]
                : [false, null];
        }

        if (!is_object($value)) {
            return [false, null];
        }

        $publicProperties = get_object_vars($value);

        if (array_key_exists($segment, $publicProperties)) {
            return [true, $publicProperties[$segment]];
        }

        $methodSuffix = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $segment)));

        foreach (['get'.$methodSuffix, 'is'.$methodSuffix, 'has'.$methodSuffix] as $method) {
            if (!method_exists($value, $method)) {
                continue;
            }

            $reflection = new \ReflectionMethod($value, $method);

            if (!$reflection->isPublic() || 0 !== $reflection->getNumberOfRequiredParameters()) {
                continue;
            }

            return [true, $reflection->invoke($value)];
        }

        return [false, null];
    }
}
