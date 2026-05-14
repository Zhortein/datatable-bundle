<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

final readonly class DoctrineFieldReference
{
    public function __construct(
        private string $alias,
        private string $field,
    ) {
        if ('' === trim($this->alias)) {
            throw new \InvalidArgumentException('The Doctrine field reference alias cannot be empty.');
        }

        if ('' === trim($this->field)) {
            throw new \InvalidArgumentException('The Doctrine field reference field cannot be empty.');
        }
    }

    public static function fromString(string $fieldReference): self
    {
        if (!str_contains($fieldReference, '.')) {
            throw new \InvalidArgumentException(sprintf('The Doctrine field reference "%s" must contain an alias and a field.', $fieldReference));
        }

        [$alias, $field] = explode('.', $fieldReference, 2);

        return new self($alias, $field);
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function toString(): string
    {
        return sprintf('%s.%s', $this->alias, $this->field);
    }

    public function toResultAlias(): string
    {
        return sprintf('%s_%s', $this->alias, $this->field);
    }
}
