<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\DateTime;

interface DateTimeFormatterInterface
{
    public function format(mixed $value): string;
}
