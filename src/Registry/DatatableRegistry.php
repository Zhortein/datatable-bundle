<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Registry;

use Psr\Container\ContainerInterface;
use Zhortein\DatatableBundle\Contract\DatatableInterface;
use Zhortein\DatatableBundle\Exception\DatatableNotFoundException;
use Zhortein\DatatableBundle\Exception\InvalidDatatableException;

final readonly class DatatableRegistry
{
    /**
     * @param array<string, string> $datatableServiceIds
     * @param array<string, string> $datatableProviderNames
     */
    public function __construct(
        private ContainerInterface $datatables,
        private array $datatableServiceIds = [],
        private array $datatableProviderNames = [],
    ) {
    }

    public function has(string $name): bool
    {
        return isset($this->datatableServiceIds[$name]) && $this->datatables->has($name);
    }

    public function get(string $name): DatatableInterface
    {
        if (!$this->has($name)) {
            throw new DatatableNotFoundException(sprintf('The datatable "%s" is not registered.', $name));
        }

        $datatable = $this->datatables->get($name);

        if (!$datatable instanceof DatatableInterface) {
            throw new InvalidDatatableException(sprintf('The datatable "%s" must implement "%s".', $name, DatatableInterface::class));
        }

        return $datatable;
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->datatableServiceIds);
    }

    /**
     * @return array<string, string>
     */
    public function getServiceIds(): array
    {
        return $this->datatableServiceIds;
    }

    public function getProviderName(string $name): ?string
    {
        return $this->datatableProviderNames[$name] ?? null;
    }
}
