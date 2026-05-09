<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Action;

use Zhortein\DatatableBundle\Definition\ActionDefinition;
use Zhortein\DatatableBundle\Exception\MissingRouteParameterValueException;

final readonly class RowActionRouteParameterResolver
{
    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    public function resolve(ActionDefinition $action, array $row): array
    {
        $resolvedParameters = [];

        foreach ($action->getRouteParameters() as $routeParameterName => $rowKey) {
            $resolvedParameters[$routeParameterName] = $this->resolveRowValue(
                action: $action,
                row: $row,
                routeParameterName: $routeParameterName,
                rowKey: $rowKey,
            );
        }

        return $resolvedParameters;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveRowValue(
        ActionDefinition $action,
        array $row,
        string $routeParameterName,
        string $rowKey,
    ): mixed {
        foreach ($this->getCandidateKeys($rowKey) as $candidateKey) {
            if (array_key_exists($candidateKey, $row)) {
                return $row[$candidateKey];
            }
        }

        throw new MissingRouteParameterValueException(sprintf('Unable to resolve route parameter "%s" for row action "%s" from row key "%s".', $routeParameterName, $action->getName(), $rowKey));
    }

    /**
     * @return list<string>
     */
    private function getCandidateKeys(string $rowKey): array
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
}
