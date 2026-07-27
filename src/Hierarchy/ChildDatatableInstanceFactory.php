<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Hierarchy;

use Zhortein\DatatableBundle\Definition\ChildDatatableDefinition;

/**
 * @internal
 */
final readonly class ChildDatatableInstanceFactory
{
    private const string PREFIX = 'zd-child-d';
    private const int HASH_LENGTH = 43;

    public function create(
        string $parentDatatableName,
        string $parentInstance,
        string $childDatatableName,
        mixed $rowIdentifier,
        int $depth,
    ): string {
        $this->assertDepth($depth);
        $rowIdentifier = $this->normalizeRowIdentifier($rowIdentifier);
        $payload = json_encode([
            'parent_datatable' => trim($parentDatatableName),
            'parent_instance' => trim($parentInstance),
            'child_datatable' => trim($childDatatableName),
            'row_identifier' => $rowIdentifier,
            'depth' => $depth,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $hash = rtrim(strtr(base64_encode(hash('sha256', $payload, true)), '+/', '-_'), '=');

        return sprintf('%s%d-%s', self::PREFIX, $depth, $hash);
    }

    public function parseDepth(string $instance): int
    {
        if (
            1 !== preg_match(
                sprintf('/^%s([1-%d])-([A-Za-z0-9_-]{%d})$/D', preg_quote(self::PREFIX, '/'), ChildDatatableDefinition::MAX_DEPTH, self::HASH_LENGTH),
                $instance,
                $matches,
            )
            || !isset($matches[1])
        ) {
            throw new \InvalidArgumentException('The child datatable instance key is invalid.');
        }

        return (int) $matches[1];
    }

    private function assertDepth(int $depth): void
    {
        if ($depth < 1 || $depth > ChildDatatableDefinition::MAX_DEPTH) {
            throw new \InvalidArgumentException(sprintf('A child datatable depth must be between 1 and %d.', ChildDatatableDefinition::MAX_DEPTH));
        }
    }

    private function normalizeRowIdentifier(mixed $rowIdentifier): bool|float|int|string
    {
        if ($rowIdentifier instanceof \BackedEnum) {
            $rowIdentifier = $rowIdentifier->value;
        } elseif ($rowIdentifier instanceof \Stringable) {
            $rowIdentifier = (string) $rowIdentifier;
        }

        if (!is_scalar($rowIdentifier) || (is_string($rowIdentifier) && '' === trim($rowIdentifier))) {
            throw new \InvalidArgumentException(sprintf('A child datatable parent row identifier must be a non-empty scalar, a backed enum or Stringable; "%s" given.', get_debug_type($rowIdentifier)));
        }

        return $rowIdentifier;
    }
}
