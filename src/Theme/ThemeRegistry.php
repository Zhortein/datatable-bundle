<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Theme;

use Zhortein\DatatableBundle\Contract\ThemeInterface;
use Zhortein\DatatableBundle\Exception\DuplicateThemeException;
use Zhortein\DatatableBundle\Exception\ThemeNotFoundException;

final readonly class ThemeRegistry
{
    public const string SERVICE_TAG = 'zhortein_datatable.theme';

    /**
     * @var array<string, ThemeInterface>
     */
    private array $themes;

    /**
     * @param iterable<ThemeInterface> $themes
     */
    public function __construct(iterable $themes)
    {
        $resolvedThemes = [];

        foreach ($themes as $theme) {
            $name = $theme->getMetadata()->getName();

            if (isset($resolvedThemes[$name])) {
                throw new DuplicateThemeException(sprintf('The datatable theme "%s" is registered more than once.', $name));
            }

            $resolvedThemes[$name] = $theme;
        }

        $this->themes = $resolvedThemes;
    }

    public function get(string $name): ThemeInterface
    {
        return $this->themes[$name] ?? throw new ThemeNotFoundException(sprintf(
            'The datatable theme "%s" is not registered. Available themes: %s.',
            $name,
            implode(', ', array_keys($this->themes)),
        ));
    }

    public function has(string $name): bool
    {
        return isset($this->themes[$name]);
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->themes);
    }
}
