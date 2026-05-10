<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

interface DatatablePreferenceProviderInterface
{
    public function getPreference(string $datatableName): DatatablePreference;
}
