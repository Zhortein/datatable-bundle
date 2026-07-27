<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Definition;

final readonly class ChildDatatableDefinition
{
    public const int MAX_DEPTH = 3;
    public const int MAX_CONTEXT_VALUES = 64;
    public const int MAX_NAME_LENGTH = 128;
    public const int MAX_CONTEXT_KEY_LENGTH = 128;

    private string $name;

    /**
     * @var array<string, ChildContextValue>
     */
    private array $context;

    /**
     * @param array<array-key, mixed> $context
     */
    public function __construct(
        string $name,
        array $context = [],
        private ?string $expandLabel = null,
        private ?string $collapseLabel = null,
        private int $maxDepth = self::MAX_DEPTH,
    ) {
        $name = trim($name);

        if (
            strlen($name) > self::MAX_NAME_LENGTH
            || 1 !== preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/D', $name)
        ) {
            throw new \InvalidArgumentException(sprintf('A child datatable name must be at most %d characters, start with an alphanumeric character and contain only alphanumeric characters, dots, underscores or hyphens.', self::MAX_NAME_LENGTH));
        }

        if ($this->maxDepth < 1 || $this->maxDepth > self::MAX_DEPTH) {
            throw new \InvalidArgumentException(sprintf('A child datatable maximum depth must be between 1 and %d.', self::MAX_DEPTH));
        }

        if (count($context) > self::MAX_CONTEXT_VALUES) {
            throw new \InvalidArgumentException(sprintf('A child datatable cannot propagate more than %d context values.', self::MAX_CONTEXT_VALUES));
        }

        $normalizedContext = [];

        foreach ($context as $key => $value) {
            if (
                !is_string($key)
                || '' === trim($key)
                || strlen(trim($key)) > self::MAX_CONTEXT_KEY_LENGTH
                || 1 === preg_match('/[\x00-\x1F\x7F]/', $key)
            ) {
                throw new \InvalidArgumentException(sprintf('A child datatable context key must be a non-empty string of at most %d characters without control characters.', self::MAX_CONTEXT_KEY_LENGTH));
            }

            if (!$value instanceof ChildContextValue) {
                throw new \InvalidArgumentException(sprintf('A child datatable context value must be an instance of "%s"; "%s" given.', ChildContextValue::class, get_debug_type($value)));
            }

            $key = trim($key);

            if (array_key_exists($key, $normalizedContext)) {
                throw new \InvalidArgumentException(sprintf('The child datatable context key "%s" is declared more than once.', $key));
            }

            $normalizedContext[$key] = $value;
        }

        $this->name = $name;
        $this->context = $normalizedContext;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, ChildContextValue>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getExpandLabel(): ?string
    {
        return $this->expandLabel;
    }

    public function getCollapseLabel(): ?string
    {
        return $this->collapseLabel;
    }

    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }
}
