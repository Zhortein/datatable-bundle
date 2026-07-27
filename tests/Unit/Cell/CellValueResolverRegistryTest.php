<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Cell;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Cell\CellValueResolverRegistry;
use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;
use Zhortein\DatatableBundle\Exception\CellValueResolverNotFoundException;
use Zhortein\DatatableBundle\Exception\DuplicateCellValueResolverException;

final class CellValueResolverRegistryTest extends TestCase
{
    public function test_it_registers_and_resolves_named_services(): void
    {
        $resolver = $this->createResolver('status');
        $registry = new CellValueResolverRegistry([$resolver]);

        self::assertTrue($registry->has('status'));
        self::assertSame($resolver, $registry->get('status'));
        self::assertSame(['status'], $registry->getNames());
    }

    public function test_it_rejects_duplicate_names(): void
    {
        $this->expectException(DuplicateCellValueResolverException::class);
        $this->expectExceptionMessage('A cell value resolver named "status" is already registered.');

        new CellValueResolverRegistry([
            $this->createResolver('status'),
            $this->createResolver('status'),
        ]);
    }

    public function test_it_rejects_an_unknown_name(): void
    {
        $this->expectException(CellValueResolverNotFoundException::class);
        $this->expectExceptionMessage('The cell value resolver "unknown" is not registered.');

        new CellValueResolverRegistry()->get('unknown');
    }

    private function createResolver(string $name): CellValueResolverInterface
    {
        return new class($name) implements CellValueResolverInterface {
            public function __construct(
                private readonly string $name,
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function resolve(CellContext $context): mixed
            {
                return $context->getValue();
            }
        };
    }
}
