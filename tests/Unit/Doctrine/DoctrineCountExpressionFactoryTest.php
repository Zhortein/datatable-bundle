<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Doctrine\DoctrineCountExpressionFactory;
use Zhortein\DatatableBundle\Enum\AggregateFunction;

final class DoctrineCountExpressionFactoryTest extends TestCase
{
    public function test_it_uses_plain_count_without_aggregate_or_custom_join(): void
    {
        $definition = new DatatableDefinition('users');
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->identifier = ['id'];

        self::assertSame('COUNT(e)', new DoctrineCountExpressionFactory()->create($definition, $metadata));
    }

    public function test_it_uses_distinct_identifier_count_with_aggregate_column(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addAggregateColumn(
            name: 'auditCount',
            field: 'audit.id',
            function: AggregateFunction::Count,
        );

        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->identifier = ['id'];

        self::assertSame('COUNT(DISTINCT e.id)', new DoctrineCountExpressionFactory()->create($definition, $metadata));
    }

    public function test_it_uses_distinct_identifier_count_with_custom_join(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addCustomJoin(
            alias: 'audit',
            targetEntityClass: \stdClass::class,
            condition: 'audit.objectId = e.id',
        );

        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->identifier = ['uuid'];

        self::assertSame('COUNT(DISTINCT e.uuid)', new DoctrineCountExpressionFactory()->create($definition, $metadata));
    }

    public function test_it_falls_back_to_distinct_entity_count_for_composite_identifier(): void
    {
        $definition = new DatatableDefinition('users');
        $definition->addCustomJoin(
            alias: 'audit',
            targetEntityClass: \stdClass::class,
            condition: 'audit.objectId = e.id',
        );

        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->identifier = ['first', 'second'];

        self::assertSame('COUNT(DISTINCT e)', new DoctrineCountExpressionFactory()->create($definition, $metadata));
    }
}
