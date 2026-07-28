<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

final readonly class DatatablePreferenceCsrfTokenIdGenerator
{
    public static function generate(string $datatableName, string $instance): string
    {
        return 'zhortein_datatable_preferences_'.hash('sha256', $datatableName."\0".$instance);
    }
}
