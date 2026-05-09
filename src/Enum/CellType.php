<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Enum;

enum CellType: string
{
    case Array = 'array';
    case Boolean = 'boolean';
    case Datetime = 'datetime';
    case Default = 'default';
    case Enum = 'enum';
    case Numeric = 'numeric';
    case String = 'string';

    public static function fromNullableString(?string $type): self
    {
        if (null === $type || '' === trim($type)) {
            return self::Default;
        }

        return self::tryFrom(strtolower(trim($type))) ?? self::Default;
    }

    public function getTemplateName(): string
    {
        return $this->value;
    }
}
