<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;
use Zhortein\DatatableBundle\Contract\IconRendererInterface;
use Zhortein\DatatableBundle\Contract\IconResolverInterface;

final class IconExtension extends AbstractExtension
{
    public function __construct(
        private readonly IconRendererInterface $renderer,
        private readonly IconResolverInterface $resolver,
    ) {
    }

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('zhortein_datatable_icon', $this->render(...), ['is_safe' => ['html']]),
            new TwigFunction('zhortein_datatable_icon_key', $this->renderKey(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    public function render(?string $icon, array $attributes = [], ?string $label = null): Markup
    {
        return new Markup(
            null === $icon ? '' : $this->renderer->render($icon, $attributes, $label),
            'UTF-8',
        );
    }

    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    public function renderKey(string $key, array $attributes = [], ?string $label = null): Markup
    {
        return $this->render($this->resolver->resolve($key), $attributes, $label);
    }
}
