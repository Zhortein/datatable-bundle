<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Icon;

use Zhortein\DatatableBundle\Contract\IconRendererInterface;

final readonly class SymfonyUxIconRenderer implements IconRendererInterface
{
    /**
     * @var (\Closure(string, array<string, string|bool>): string)|null
     */
    private ?\Closure $renderIcon;

    public function __construct(
        ?object $renderer,
    ) {
        if (null === $renderer) {
            $this->renderIcon = null;

            return;
        }

        if (!is_callable([$renderer, 'renderIcon'])) {
            throw new \InvalidArgumentException('The Symfony UX icon renderer must expose a renderIcon() method.');
        }

        /** @var callable(string, array<string, string|bool>): string $callable */
        $callable = [$renderer, 'renderIcon'];
        $this->renderIcon = \Closure::fromCallable($callable);
    }

    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    public function render(string $icon, array $attributes = [], ?string $label = null): string
    {
        $icon = trim($icon);

        if ('' === $icon) {
            return '';
        }

        if (null === $this->renderIcon) {
            throw new \LogicException('The Symfony UX Icons renderer service is unavailable.');
        }

        [$icon, $legacyClasses] = $this->normalizeLegacyBootstrapIcon($icon);

        if (null !== $legacyClasses) {
            $attributeClasses = $attributes['class'] ?? null;
            $attributes['class'] = trim(sprintf(
                '%s %s',
                $legacyClasses,
                is_string($attributeClasses) ? $attributeClasses : '',
            ));
        }

        $uxAttributes = [];

        foreach ($attributes as $name => $value) {
            if (!$this->isAllowedAttributeName($name)) {
                continue;
            }

            if (is_string($value) || is_bool($value)) {
                $uxAttributes[$name] = $value;
            } elseif (is_int($value) || is_float($value)) {
                $uxAttributes[$name] = (string) $value;
            }
        }

        unset($uxAttributes['aria-hidden'], $uxAttributes['aria-label'], $uxAttributes['role']);

        if (null === $label || '' === trim($label)) {
            $uxAttributes['aria-hidden'] = 'true';
        } else {
            $uxAttributes['role'] = 'img';
            $uxAttributes['aria-label'] = $label;
        }

        return ($this->renderIcon)($icon, $uxAttributes);
    }

    /**
     * @return array{string, string|null}
     */
    private function normalizeLegacyBootstrapIcon(string $icon): array
    {
        if (1 !== preg_match('/^bi bi-([a-z0-9-]+)(?:\s+(.+))?$/', $icon, $matches)) {
            return [$icon, null];
        }

        return [
            sprintf('bi:%s', $matches[1]),
            $matches[2] ?? null,
        ];
    }

    private function isAllowedAttributeName(string $name): bool
    {
        return 1 === preg_match(
            '/^(?:class|id|title|role|width|height|fill|stroke|stroke-width|focusable|aria-[a-z0-9_.:-]+|data-[a-z0-9_.:-]+)$/i',
            $name,
        );
    }
}
