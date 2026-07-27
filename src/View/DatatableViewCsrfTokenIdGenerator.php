<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

final readonly class DatatableViewCsrfTokenIdGenerator
{
    public static function generate(string $datatableName, string $instance): string
    {
        return 'zhortein_datatable_views_'.hash('sha256', $datatableName."\0".$instance);
    }
}
