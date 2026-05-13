<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum FilterLayout: string
{
    case Toolbar = 'toolbar';
    case Header = 'header';
    case None = 'none';

    public static function fromNullableString(?string $layout): self
    {
        if (null === $layout || '' === trim($layout)) {
            return self::Toolbar;
        }

        return self::tryFrom(strtolower(trim($layout))) ?? self::Toolbar;
    }

    public function rendersToolbarFilters(): bool
    {
        return self::Toolbar === $this;
    }

    public function rendersHeaderFilters(): bool
    {
        return self::Header === $this;
    }

    public function rendersFilters(): bool
    {
        return self::None !== $this;
    }
}
