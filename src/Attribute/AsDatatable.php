<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsDatatable
{
    public function __construct(
        public ?string $name = null,
        public ?string $label = null,
        public ?string $provider = null,
    ) {
    }
}
