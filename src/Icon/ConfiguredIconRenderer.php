<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Icon;

use Zhortein\DatatableBundle\Contract\IconRendererInterface;

final readonly class ConfiguredIconRenderer implements IconRendererInterface
{
    public function __construct(
        private CssClassIconRenderer $defaultRenderer,
        private string $provider = 'css',
        private ?SymfonyUxIconRenderer $uxIconRenderer = null,
    ) {
    }

    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    public function render(string $icon, array $attributes = [], ?string $label = null): string
    {
        if ('ux_icons' === $this->provider && null !== $this->uxIconRenderer) {
            try {
                return $this->uxIconRenderer->render($icon, $attributes, $label);
            } catch (\Throwable) {
                // A missing remote or local UX icon must not remove an accessible control.
            }
        }

        return $this->defaultRenderer->render($this->normalizeUxBootstrapFallback($icon), $attributes, $label);
    }

    private function normalizeUxBootstrapFallback(string $icon): string
    {
        if ('ux_icons' !== $this->provider || 1 !== preg_match('/^bi:([a-z0-9-]+)$/', $icon, $matches)) {
            return $icon;
        }

        return sprintf('bi bi-%s', $matches[1]);
    }
}
