<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

use Zhortein\DatatableBundle\Contract\DatatableViewProviderInterface;
use Zhortein\DatatableBundle\Exception\UnsupportedDatatableViewProviderException;

final readonly class NullDatatableViewProvider implements DatatableViewProviderInterface
{
    public function list(DatatableViewScope $scope, ?string $ownerIdentifier): array
    {
        return [];
    }

    public function load(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
    ): ?DatatableView {
        return null;
    }

    public function create(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $name,
        DatatableViewState $state,
        bool $default = false,
    ): DatatableView {
        throw $this->unsupported();
    }

    public function rename(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $name,
        string $expectedRevision,
    ): DatatableView {
        throw $this->unsupported();
    }

    public function update(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        DatatableViewState $state,
        string $expectedRevision,
    ): DatatableView {
        throw $this->unsupported();
    }

    public function setDefault(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $expectedRevision,
    ): DatatableView {
        throw $this->unsupported();
    }

    public function delete(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $expectedRevision,
    ): void {
        throw $this->unsupported();
    }

    private function unsupported(): UnsupportedDatatableViewProviderException
    {
        return new UnsupportedDatatableViewProviderException('No datatable view provider is configured.');
    }
}
