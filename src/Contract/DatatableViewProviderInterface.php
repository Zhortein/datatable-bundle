<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Contract;

use Zhortein\DatatableBundle\View\DatatableView;
use Zhortein\DatatableBundle\View\DatatableViewMetadata;
use Zhortein\DatatableBundle\View\DatatableViewScope;
use Zhortein\DatatableBundle\View\DatatableViewState;

interface DatatableViewProviderInterface
{
    /**
     * @return list<DatatableViewMetadata>
     */
    public function list(DatatableViewScope $scope, ?string $ownerIdentifier): array;

    public function load(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
    ): ?DatatableView;

    public function create(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $name,
        DatatableViewState $state,
        bool $default = false,
    ): DatatableView;

    public function rename(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $name,
        string $expectedRevision,
    ): DatatableView;

    public function update(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        DatatableViewState $state,
        string $expectedRevision,
    ): DatatableView;

    public function setDefault(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $expectedRevision,
    ): DatatableView;

    public function delete(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $expectedRevision,
    ): void;
}
