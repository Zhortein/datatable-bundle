<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Hierarchy;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Contract\ChildDatatableAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\InvalidDatatableContextException;

/**
 * @internal
 */
final readonly class ChildDatatableRequestResolver
{
    public function __construct(
        private DatatableContextTransport $contextTransport,
        private ChildDatatableInstanceFactory $instanceFactory,
        private ChildDatatableAuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function supports(string $instance): bool
    {
        return $this->instanceFactory->isChildInstance($instance);
    }

    public function resolve(Request $request, DatatableDefinition $definition): ChildDatatableRequest
    {
        $instance = $this->readRequiredString(
            $request,
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER,
        );
        $token = $this->readRequiredString(
            $request,
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER,
        );

        try {
            $depth = $this->instanceFactory->parseDepth($instance);
            $context = $this->contextTransport->restore(
                token: $token,
                datatableName: $definition->getName(),
                instance: $instance,
                context: $definition->getContext(),
            );
        } catch (InvalidDatatableContextException|\InvalidArgumentException $exception) {
            throw new BadRequestHttpException('The child datatable request is invalid.', $exception);
        }

        if (!$this->authorizationChecker->isGranted(new ChildDatatableAuthorizationContext(
            childDatatableName: $definition->getName(),
            childInstance: $instance,
            depth: $depth,
            context: $context,
        ))) {
            throw new AccessDeniedHttpException('Access to the child datatable was denied.');
        }

        $definition->setContext($context);

        return new ChildDatatableRequest($instance, $depth);
    }

    private function readRequiredString(Request $request, string $name): string
    {
        $value = $request->query->all()[$name] ?? null;

        if (!is_string($value) || '' === $value) {
            throw new BadRequestHttpException(sprintf('The "%s" query parameter must be a non-empty string.', $name));
        }

        return $value;
    }
}
