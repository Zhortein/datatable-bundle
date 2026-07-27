<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

final readonly class DatatableViewAuthorizationContext
{
    public function __construct(
        private DatatableViewScope $scope,
        private ?string $ownerIdentifier,
        private ?DatatableView $view = null,
    ) {
    }

    public function getScope(): DatatableViewScope
    {
        return $this->scope;
    }

    public function getOwnerIdentifier(): ?string
    {
        return $this->ownerIdentifier;
    }

    public function getView(): ?DatatableView
    {
        return $this->view;
    }
}
