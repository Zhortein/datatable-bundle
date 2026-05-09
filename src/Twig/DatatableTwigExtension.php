<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Twig;

use Twig\Attribute\AsTwigFunction;
use Zhortein\DatatableBundle\Factory\DatatableDefinitionFactory;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final readonly class DatatableTwigExtension
{
    public function __construct(
        private DatatableDefinitionFactory $definitionFactory,
        private DatatableRenderer $renderer,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[AsTwigFunction('zhortein_datatable', isSafe: ['html'])]
    public function renderDatatable(string $name, array $options = []): string
    {
        return $this->renderer->render(
            $this->definitionFactory->create($name),
            $options,
        );
    }
}
