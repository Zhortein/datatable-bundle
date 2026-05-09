<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Twig;

use Twig\Attribute\AsTwigFunction;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Registry\DatatableRegistry;
use Zhortein\DatatableBundle\Renderer\DatatableRenderer;

final readonly class DatatableTwigExtension
{
    public function __construct(
        private DatatableRegistry $registry,
        private DatatableRenderer $renderer,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[AsTwigFunction('zhortein_datatable', isSafe: ['html'])]
    public function renderDatatable(string $name, array $options = []): string
    {
        $datatable = $this->registry->get($name);
        $definition = new DatatableDefinition($name);

        $datatable->buildDatatable($definition);

        return $this->renderer->render($definition, $options);
    }
}
