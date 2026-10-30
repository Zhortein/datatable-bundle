<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\State;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zhortein\DatatableBundle\Context\DatatableContextTransport;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;

/**
 * Builds the address of a page whose datatable opens in a given state, in the order its definition declares and at
 * the configured page size.
 *
 * The state replaces the datatable's whole state: a filter the page sets by default holds only if the state repeats it.
 */
final readonly class DatatableStateUrlGenerator
{
    public function __construct(
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableContextTransport $contextTransport,
        private DatatableStateUrlSerializer $serializer,
        private UrlGeneratorInterface $urlGenerator,
        private int $defaultPageSize,
    ) {
    }

    /**
     * @param array<string, mixed> $routeParameters the parameters of the page's route
     * @param array<string, mixed> $filters         the simple filters, by name
     * @param array<string, mixed> $advancedFilters the advanced filter expression, as the URL state carries it
     * @param array<string, mixed> $context         the browser-safe context the page renders the datatable in
     * @param string|null          $instance        the instance the page renders the datatable as, its name by default
     */
    public function generate(
        string $route,
        array $routeParameters,
        string $datatable,
        array $filters = [],
        array $advancedFilters = [],
        array $context = [],
        ?string $instance = null,
    ): string {
        $definition = $this->definitionFactory->create($datatable);
        $instance = $this->contextTransport->normalizeInstance($instance ?? $datatable);
        $token = $this->contextTransport->createToken(
            $datatable,
            $instance,
            $definition->getContext()->withBrowserValues($context),
        );
        $state = DatatableState::create(
            pageSize: $this->defaultPageSize,
            filters: $filters,
            advancedFilters: $advancedFilters,
            sorts: $definition->getDefaultSorts(),
        );
        $parameter = $this->serializer->createParameterName($datatable, $instance, $token);
        $payload = $this->serializer->serialize($state);

        return $this->urlGenerator->generate($route, [...$routeParameters, $parameter => $payload]);
    }
}
