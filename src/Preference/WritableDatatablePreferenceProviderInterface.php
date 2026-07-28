<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

interface WritableDatatablePreferenceProviderInterface extends ScopedDatatablePreferenceProviderInterface
{
    public function savePreference(
        DatatablePreferenceScope $scope,
        DatatablePreference $preference,
    ): void;

    public function resetPreference(DatatablePreferenceScope $scope): void;
}
