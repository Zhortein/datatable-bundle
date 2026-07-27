<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Hierarchy;

/**
 * @internal
 */
final readonly class RowValueAccessor
{
    /**
     * @param array<string, mixed> $row
     *
     * @return array{bool, mixed}
     */
    public function read(array $row, string $path): array
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
