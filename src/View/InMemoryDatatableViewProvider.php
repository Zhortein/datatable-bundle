<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\View;

use Zhortein\DatatableBundle\Contract\DatatableViewProviderInterface;
use Zhortein\DatatableBundle\Exception\DatatableViewConflictException;
use Zhortein\DatatableBundle\Exception\DatatableViewNotFoundException;

/**
 * Process-local implementation for tests and examples.
 *
 * It is intentionally not the default and is not suitable for persistent
 * production storage.
 */
final class InMemoryDatatableViewProvider implements DatatableViewProviderInterface
{
    /**
     * @var array<string, array<string, DatatableView>>
     */
    private array $views = [];

    private int $nextIdentifier = 1;

    public function list(DatatableViewScope $scope, ?string $ownerIdentifier): array
    {
        $metadata = array_map(
            static fn (DatatableView $view): DatatableViewMetadata => $view->getMetadata(),
            array_values($this->views[$this->createPartitionKey($scope, $ownerIdentifier)] ?? []),
        );

        usort(
            $metadata,
            static fn (DatatableViewMetadata $left, DatatableViewMetadata $right): int => strnatcasecmp(
                $left->getName(),
                $right->getName(),
            ),
        );

        return $metadata;
    }

    public function load(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
    ): ?DatatableView {
        return $this->views[$this->createPartitionKey($scope, $ownerIdentifier)][$viewIdentifier] ?? null;
    }

    public function create(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $name,
        DatatableViewState $state,
        bool $default = false,
    ): DatatableView {
        $partitionKey = $this->createPartitionKey($scope, $ownerIdentifier);
        $name = trim($name);
        $this->assertUniqueName($partitionKey, $name);

        if ($default) {
            $this->clearDefault($partitionKey);
        }

        $identifier = 'view-'.($this->nextIdentifier++);
        $view = new DatatableView(
            DatatableViewMetadata::create($identifier, $name, '1', $default),
            $state,
        );
        $this->views[$partitionKey][$identifier] = $view;

        return $view;
    }

    public function rename(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $name,
        string $expectedRevision,
    ): DatatableView {
        $partitionKey = $this->createPartitionKey($scope, $ownerIdentifier);
        $view = $this->requireView($partitionKey, $viewIdentifier);
        $this->assertRevision($view, $expectedRevision);
        $name = trim($name);
        $this->assertUniqueName($partitionKey, $name, $viewIdentifier);

        return $this->replace(
            $partitionKey,
            $view->withMetadata($view->getMetadata()->with(
                name: $name,
                revision: $this->nextRevision($view),
            )),
        );
    }

    public function update(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        DatatableViewState $state,
        string $expectedRevision,
    ): DatatableView {
        $partitionKey = $this->createPartitionKey($scope, $ownerIdentifier);
        $view = $this->requireView($partitionKey, $viewIdentifier);
        $this->assertRevision($view, $expectedRevision);

        return $this->replace(
            $partitionKey,
            $view
                ->withState($state)
                ->withMetadata($view->getMetadata()->with(revision: $this->nextRevision($view))),
        );
    }

    public function setDefault(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $expectedRevision,
    ): DatatableView {
        $partitionKey = $this->createPartitionKey($scope, $ownerIdentifier);
        $view = $this->requireView($partitionKey, $viewIdentifier);
        $this->assertRevision($view, $expectedRevision);

        if ($view->getMetadata()->isDefault()) {
            return $view;
        }

        $this->clearDefault($partitionKey);
        $view = $view->withMetadata($view->getMetadata()->with(
            revision: $this->nextRevision($view),
            default: true,
        ));

        return $this->replace($partitionKey, $view);
    }

    public function delete(
        DatatableViewScope $scope,
        ?string $ownerIdentifier,
        string $viewIdentifier,
        string $expectedRevision,
    ): void {
        $partitionKey = $this->createPartitionKey($scope, $ownerIdentifier);
        $view = $this->requireView($partitionKey, $viewIdentifier);
        $this->assertRevision($view, $expectedRevision);

        unset($this->views[$partitionKey][$viewIdentifier]);
    }

    private function createPartitionKey(DatatableViewScope $scope, ?string $ownerIdentifier): string
    {
        return $scope->getStorageKey().':'.hash('sha256', $ownerIdentifier ?? "\0anonymous");
    }

    private function requireView(string $partitionKey, string $viewIdentifier): DatatableView
    {
        $view = $this->views[$partitionKey][$viewIdentifier] ?? null;

        if (null === $view) {
            throw new DatatableViewNotFoundException(sprintf('The datatable view "%s" does not exist.', $viewIdentifier));
        }

        return $view;
    }

    private function assertRevision(DatatableView $view, string $expectedRevision): void
    {
        if ($view->getMetadata()->getRevision() !== $expectedRevision) {
            throw new DatatableViewConflictException('The datatable view was modified by another request.');
        }
    }

    private function assertUniqueName(
        string $partitionKey,
        string $name,
        ?string $excludedIdentifier = null,
    ): void {
        // Constructing metadata applies the public validation rules.
        DatatableViewMetadata::create('validation', $name, '1');

        foreach ($this->views[$partitionKey] ?? [] as $view) {
            if (
                $view->getMetadata()->getIdentifier() !== $excludedIdentifier
                && 0 === strcasecmp($view->getMetadata()->getName(), $name)
            ) {
                throw new DatatableViewConflictException(sprintf('A datatable view named "%s" already exists in this scope.', $name));
            }
        }
    }

    private function clearDefault(string $partitionKey): void
    {
        foreach ($this->views[$partitionKey] ?? [] as $identifier => $view) {
            if (!$view->getMetadata()->isDefault()) {
                continue;
            }

            $this->views[$partitionKey][$identifier] = $view->withMetadata(
                $view->getMetadata()->with(
                    revision: $this->nextRevision($view),
                    default: false,
                ),
            );
        }
    }

    private function nextRevision(DatatableView $view): string
    {
        $revision = $view->getMetadata()->getRevision();

        return ctype_digit($revision) ? (string) ((int) $revision + 1) : hash('sha256', $revision."\0next");
    }

    private function replace(string $partitionKey, DatatableView $view): DatatableView
    {
        $this->views[$partitionKey][$view->getMetadata()->getIdentifier()] = $view;

        return $view;
    }
}
