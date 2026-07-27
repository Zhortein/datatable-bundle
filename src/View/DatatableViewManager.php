<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

use Zhortein\DatatableBundle\Contract\DatatableViewAuthorizationCheckerInterface;
use Zhortein\DatatableBundle\Contract\DatatableViewProviderInterface;
use Zhortein\DatatableBundle\Enum\DatatableViewOperation;
use Zhortein\DatatableBundle\Exception\DatatableViewAccessDeniedException;
use Zhortein\DatatableBundle\Exception\DatatableViewNotFoundException;

final readonly class DatatableViewManager
{
    public function __construct(
        private DatatableViewProviderInterface $provider,
        private DatatableViewAuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @return list<DatatableViewMetadata>
     */
    public function list(DatatableViewScope $scope, ?string $ownerIdentifier): array
    {
        $this->denyUnlessGranted(DatatableViewOperation::List, $scope, $ownerIdentifier);

        return $this->provider->list($scope, $ownerIdentifier);
    }

    public function load(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
    ): DatatableView {
        $view = $this->requireView($scope, $ownerIdentifier, $viewIdentifier);
        $this->denyUnlessGranted(DatatableViewOperation::Load, $scope, $ownerIdentifier, $view);

        return $view;
    }

    public function create(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $name,
        DatatableViewState $state,
        bool $default = false,
    ): DatatableView {
        $this->denyUnlessGranted(DatatableViewOperation::Create, $scope, $ownerIdentifier);

        return $this->provider->create($scope, $ownerIdentifier, $name, $state, $default);
    }

    public function rename(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $name,
        string $expectedRevision,
    ): DatatableView {
        $view = $this->requireView($scope, $ownerIdentifier, $viewIdentifier);
        $this->denyUnlessGranted(DatatableViewOperation::Rename, $scope, $ownerIdentifier, $view);

        return $this->provider->rename(
            $scope,
            $ownerIdentifier,
            $viewIdentifier,
            $name,
            $expectedRevision,
        );
    }

    public function update(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        DatatableViewState $state,
        string $expectedRevision,
    ): DatatableView {
        $view = $this->requireView($scope, $ownerIdentifier, $viewIdentifier);
        $this->denyUnlessGranted(DatatableViewOperation::Update, $scope, $ownerIdentifier, $view);

        return $this->provider->update(
            $scope,
            $ownerIdentifier,
            $viewIdentifier,
            $state,
            $expectedRevision,
        );
    }

    public function setDefault(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $expectedRevision,
    ): DatatableView {
        $view = $this->requireView($scope, $ownerIdentifier, $viewIdentifier);
        $this->denyUnlessGranted(DatatableViewOperation::SetDefault, $scope, $ownerIdentifier, $view);

        return $this->provider->setDefault(
            $scope,
            $ownerIdentifier,
            $viewIdentifier,
            $expectedRevision,
        );
    }

    public function delete(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $expectedRevision,
    ): void {
        $view = $this->requireView($scope, $ownerIdentifier, $viewIdentifier);
        $this->denyUnlessGranted(DatatableViewOperation::Delete, $scope, $ownerIdentifier, $view);
        $this->provider->delete($scope, $ownerIdentifier, $viewIdentifier, $expectedRevision);
    }

    private function requireView(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
    ): DatatableView {
        $view = $this->provider->load($scope, $ownerIdentifier, $viewIdentifier);

        if (null === $view) {
            throw new DatatableViewNotFoundException(sprintf(
                'The datatable view "%s" does not exist.',
                $viewIdentifier,
            ));
        }

        return $view;
    }

    private function denyUnlessGranted(
        DatatableViewOperation $operation,
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        ?DatatableView $view = null,
    ): void {
        if ($this->authorizationChecker->isGranted(
            $operation,
            new DatatableViewAuthorizationContext($scope, $ownerIdentifier, $view),
        )) {
            return;
        }

        throw new DatatableViewAccessDeniedException(sprintf(
            'The datatable view operation "%s" is not allowed.',
            $operation->value,
        ));
    }
}
