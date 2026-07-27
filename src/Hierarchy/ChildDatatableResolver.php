<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Hierarchy;

use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\ChildDatatableAccessDeniedException;

/**
 * @internal
 */
final readonly class ChildDatatableResolver
{
    public function __construct(
        private ChildDatatableContextResolver $contextResolver,
        private ChildDatatableInstanceFactory $instanceFactory,
        private DatatableContextTransport $contextTransport,
        private ChildDatatableAuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public function resolve(
        DatatableDefinition $parentDefinition,
        array $row,
        mixed $rowIdentifier,
        string $parentInstance,
        int $parentDepth = 0,
    ): ResolvedChildDatatable {
        $childDefinition = $parentDefinition->getChildDatatable();

        if (null === $childDefinition) {
            throw new \LogicException(sprintf('Datatable "%s" does not define a child datatable.', $parentDefinition->getName()));
        }

        $depth = $parentDepth + 1;

        if ($depth > $childDefinition->getMaxDepth()) {
            throw new \InvalidArgumentException(sprintf('Child datatable "%s" cannot be resolved at depth %d; its maximum depth is %d.', $childDefinition->getName(), $depth, $childDefinition->getMaxDepth()));
        }

        $context = $this->contextResolver->resolve(
            $childDefinition,
            $row,
            $parentDefinition->getContext(),
        );
        $parentInstance = $this->contextTransport->normalizeInstance($parentInstance);
        $instance = $this->instanceFactory->create(
            parentDatatableName: $parentDefinition->getName(),
            parentInstance: $parentInstance,
            childDatatableName: $childDefinition->getName(),
            rowIdentifier: $rowIdentifier,
            depth: $depth,
        );
        $resolved = new ResolvedChildDatatable(
            name: $childDefinition->getName(),
            instance: $instance,
            depth: $depth,
            context: $context,
            contextToken: $this->contextTransport->createRequiredToken(
                $childDefinition->getName(),
                $instance,
                $context,
            ),
        );

        if (!$this->authorizationChecker->isGranted(new ChildDatatableAuthorizationContext(
            childDatatableName: $resolved->getName(),
            childInstance: $resolved->getInstance(),
            depth: $resolved->getDepth(),
            context: $resolved->getContext(),
        ))) {
            throw new ChildDatatableAccessDeniedException(sprintf('Access to child datatable "%s" at depth %d was denied.', $childDefinition->getName(), $depth));
        }

        return $resolved;
    }
}
