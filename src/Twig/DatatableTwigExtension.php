<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Twig;

use Twig\Attribute\AsTwigFunction;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Preference\ScopedDatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;
use Zhortein\DatatableBundle\State\DatatableStateUrlGenerator;

final readonly class DatatableTwigExtension
{
    public function __construct(
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableRenderer $renderer,
        private DatatablePreferenceProviderInterface $preferenceProvider,
        private DatatableStateUrlGenerator $stateUrlGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[AsTwigFunction('zhortein_datatable', isSafe: ['html'])]
    public function renderDatatable(string $name, array $options = []): string
    {
        $preferenceOptions = $this->preferenceProvider instanceof ScopedDatatablePreferenceProviderInterface
            ? []
            : $this->preferenceProvider
                ->getPreference($name)
                ->toRenderOptions()
        ;

        if (array_key_exists('sorts', $options)) {
            unset($preferenceOptions['sortField'], $preferenceOptions['sortDirection']);
        } elseif (array_key_exists('sortField', $options) || array_key_exists('sortDirection', $options)) {
            unset($preferenceOptions['sorts']);
        }

        return $this->renderer->render(
            $this->definitionFactory->create($name),
            array_replace($preferenceOptions, $options),
        );
    }

    /**
     * The address of a page whose datatable opens with the filters and advanced filters of the state given.
     *
     * @param array<string, mixed> $routeParameters the parameters of the page's route
     * @param array<string, mixed> $state           its filters and advancedFilters
     * @param array<string, mixed> $options         the context and instance the page renders the datatable with
     */
    #[AsTwigFunction('zhortein_datatable_path')]
    public function datatablePath(
        string $route,
        array $routeParameters,
        string $name,
        array $state = [],
        array $options = [],
    ): string {
        return $this->stateUrlGenerator->generate(
            $route,
            $routeParameters,
            $name,
            filters: self::map($state, 'filters'),
            advancedFilters: self::map($state, 'advancedFilters'),
            context: self::map($options, 'context'),
            instance: is_string($options['instance'] ?? null) ? $options['instance'] : null,
        );
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function map(array $values, string $key): array
    {
        $map = $values[$key] ?? [];

        if (!is_array($map) || [] !== array_filter(array_keys($map), is_int(...))) {
            throw new \InvalidArgumentException(sprintf('The datatable path option "%s" must be a map.', $key));
        }

        /** @var array<string, mixed> $map */
        return $map;
    }
}
