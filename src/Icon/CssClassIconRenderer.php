<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Icon;

use Zhortein\DatatableBundle\Contract\IconRendererInterface;

final readonly class CssClassIconRenderer implements IconRendererInterface
{
    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    public function render(string $icon, array $attributes = [], ?string $label = null): string
    {
        $icon = trim($icon);

        if ('' === $icon) {
            return '';
        }

        $attributes['class'] = trim(sprintf('%s %s', $icon, $this->stringAttribute($attributes['class'] ?? null)));
        $attributes = $this->withAccessibilityAttributes($attributes, $label);

        return sprintf('<span%s></span>', $this->renderAttributes($attributes));
    }

    /**
     * @param array<string, string|bool|int|float|null> $attributes
     *
     * @return array<string, string|bool|int|float|null>
     */
    private function withAccessibilityAttributes(array $attributes, ?string $label): array
    {
        unset($attributes['aria-hidden'], $attributes['aria-label'], $attributes['role']);

        if (null === $label || '' === trim($label)) {
            $attributes['aria-hidden'] = 'true';

            return $attributes;
        }

        $attributes['role'] = 'img';
        $attributes['aria-label'] = $label;

        return $attributes;
    }

    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    private function renderAttributes(array $attributes): string
    {
        $rendered = [];

        foreach ($attributes as $name => $value) {
            if (!$this->isAllowedAttributeName($name) || null === $value || false === $value) {
                continue;
            }

            if (true === $value) {
                $rendered[] = $name;

                continue;
            }

            $rendered[] = sprintf(
                '%s="%s"',
                $name,
                htmlspecialchars((string) $value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        return [] === $rendered ? '' : ' '.implode(' ', $rendered);
    }

    private function isAllowedAttributeName(string $name): bool
    {
        return 1 === preg_match(
            '/^(?:class|id|title|role|width|height|fill|stroke|stroke-width|focusable|aria-[a-z0-9_.:-]+|data-[a-z0-9_.:-]+)$/i',
            $name,
        );
    }

    private function stringAttribute(string|bool|int|float|null $value): string
    {
        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : '';
    }
}
