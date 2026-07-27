<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Cell;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Cell\CellContext;
use Zhortein\DatatableBundle\Cell\CellContextFactory;
use Zhortein\DatatableBundle\Cell\CellValueResolverRegistry;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Contract\CellValueResolverInterface;
use Zhortein\DatatableBundle\Definition\ColumnDefinition;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;

final class CellContextFactoryTest extends TestCase
{
    public function test_it_resolves_normalized_values_identifiers_sources_and_context(): void
    {
        $definition = new DatatableDefinition('users');
        $definition
            ->setContext(new DatatableContext(['scope' => 'admin']))
            ->setOption('identifier', 'e.id')
        ;
        $column = new ColumnDefinition('e.displayName');
        $source = new \stdClass();

        $context = new CellContextFactory()->create(
            definition: $definition,
            column: $column,
            row: [
                'e_id' => 42,
                'e_displayName' => 'Alice',
            ],
            source: $source,
        );

        self::assertSame('Alice', $context->getValue());
        self::assertSame('42', $context->getRowIdentifier());
        self::assertSame($source, $context->getSource());
        self::assertSame('admin', $context->getDatatableContext()->get('scope'));
        self::assertNull($context->getDatatableContext()->get('forbidden'));
    }

    public function test_it_uses_a_named_resolver_for_a_computed_column(): void
    {
        $resolver = new class implements CellValueResolverInterface {
            public function getName(): string
            {
                return 'display_name';
            }

            public function resolve(CellContext $context): mixed
            {
                $email = $context->getRow()['email'] ?? null;
                $scope = $context->getDatatableContext()->get('scope');

                if (!is_string($email)) {
                    throw new \UnexpectedValueException('Expected the email to be a string.');
                }

                if (!is_string($scope)) {
                    throw new \UnexpectedValueException('Expected the scope to be a string.');
                }

                return sprintf(
                    '%s (%s)',
                    $email,
                    $scope,
                );
            }
        };
        $factory = new CellContextFactory(new CellValueResolverRegistry([$resolver]));
        $definition = new DatatableDefinition('users');
        $definition
            ->setContext(new DatatableContext(['scope' => 'admin']))
            ->addComputedColumn('display_name', valueResolver: 'display_name')
        ;

        $context = $factory->create(
            definition: $definition,
            column: $definition->getColumns()['display_name'],
            row: ['id' => 1, 'email' => 'alice@example.test'],
            source: ['id' => 1, 'email' => 'alice@example.test'],
        );

        self::assertSame('alice@example.test (admin)', $context->getValue());
        self::assertSame('1', $context->getRowIdentifier());
    }

    public function test_it_normalizes_backed_enum_and_stringable_identifiers(): void
    {
        $factory = new CellContextFactory();
        $definition = new DatatableDefinition('users');
        $definition->setOption('identifier', 'identifier');

        self::assertSame(
            'published',
            $factory->resolveRowIdentifier(['identifier' => CellContextFactoryStatus::Published], $definition),
        );

        self::assertSame(
            'stringable-id',
            $factory->resolveRowIdentifier([
                'identifier' => new class implements \Stringable {
                    public function __toString(): string
                    {
                        return 'stringable-id';
                    }
                },
            ], $definition),
        );
    }
}

enum CellContextFactoryStatus: string
{
    case Published = 'published';
}
