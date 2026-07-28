<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Preference;

interface ScopedDatatablePreferenceProviderInterface extends DatatablePreferenceProviderInterface
{
    public function getPreferenceForScope(DatatablePreferenceScope $scope): DatatablePreference;
}
