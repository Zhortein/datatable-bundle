<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Context;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\InvalidDatatableContextException;

final readonly class DatatableContextRequestResolver
{
    public function __construct(
        private DatatableContextTransport $transport,
    ) {
    }

    public function resolve(Request $request, DatatableDefinition $definition): string
    {
        $instance = $this->readOptionalString(
            $request,
            DatatableContextTransport::INSTANCE_QUERY_PARAMETER,
        ) ?? $definition->getName();

        try {
            $instance = $this->transport->normalizeInstance($instance);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException('The datatable instance key is invalid.', $exception);
        }

        $token = $this->readOptionalString(
            $request,
            DatatableContextTransport::CONTEXT_QUERY_PARAMETER,
        );

        if (null === $token) {
            return $instance;
        }

        try {
            $definition->setContext($this->transport->restore(
                token: $token,
                datatableName: $definition->getName(),
                instance: $instance,
                context: $definition->getContext(),
            ));
        } catch (InvalidDatatableContextException|\InvalidArgumentException $exception) {
            throw new BadRequestHttpException('The datatable context is invalid.', $exception);
        }

        return $instance;
    }

    private function readOptionalString(Request $request, string $name): ?string
    {
        $value = $request->query->all()[$name] ?? null;

        if (null === $value) {
            return null;
        }

        if (!is_string($value) || '' === $value) {
            throw new BadRequestHttpException(sprintf('The "%s" query parameter must be a non-empty string.', $name));
        }

        return $value;
    }
}
