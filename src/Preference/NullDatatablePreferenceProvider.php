<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

final readonly class NullDatatablePreferenceProvider implements DatatablePreferenceProviderInterface
{
    public function getPreference(string $datatableName): DatatablePreference
    {
        return DatatablePreference::empty();
    }
}
