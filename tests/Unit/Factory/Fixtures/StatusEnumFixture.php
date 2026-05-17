<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Factory\Fixtures;

enum StatusEnumFixture: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Inactive = 'inactive';
}
