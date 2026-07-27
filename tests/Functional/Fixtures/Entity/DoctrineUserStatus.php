<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Functional\Fixtures\Entity;

enum DoctrineUserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
