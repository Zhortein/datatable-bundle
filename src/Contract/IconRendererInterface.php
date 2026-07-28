<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

interface IconRendererInterface
{
    /**
     * @param array<string, string|bool|int|float|null> $attributes
     */
    public function render(string $icon, array $attributes = [], ?string $label = null): string;
}
