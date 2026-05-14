<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Doctrine;

use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Provider\DoctrineOrmDataProvider;

final readonly class DoctrineFieldReferenceResolver
{
    public function normalize(string $field, DatatableDefinition $definition): DoctrineFieldReference
    {
        if (!str_contains($field, '.')) {
            return new DoctrineFieldReference(DoctrineOrmDataProvider::MAIN_ALIAS, $field);
        }

        $reference = DoctrineFieldReference::fromString($field);

        if (DoctrineOrmDataProvider::MAIN_ALIAS === $reference->getAlias()) {
            return $reference;
        }

        if (array_key_exists($reference->getAlias(), $definition->getCustomJoins())) {
            return $reference;
        }

        if (array_key_exists($reference->getAlias(), $definition->getJoins())) {
            return $reference;
        }

        throw new \InvalidArgumentException(sprintf('The Doctrine alias "%s" is not declared for field "%s".', $reference->getAlias(), $field));
    }
}
