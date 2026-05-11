<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Twig;

use Twig\Attribute\AsTwigFunction;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Preference\DatatablePreferenceProviderInterface;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final readonly class DatatableTwigExtension
{
    public function __construct(
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableRenderer $renderer,
        private DatatablePreferenceProviderInterface $preferenceProvider,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[AsTwigFunction('zhortein_datatable', isSafe: ['html'])]
    public function renderDatatable(string $name, array $options = []): string
    {
        $preferenceOptions = $this->preferenceProvider
            ->getPreference($name)
            ->toRenderOptions()
        ;

        return $this->renderer->render(
            $this->definitionFactory->create($name),
            array_replace($preferenceOptions, $options),
        );
    }
}
