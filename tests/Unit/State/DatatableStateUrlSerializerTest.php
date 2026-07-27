<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\State;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Exception\InvalidDatatableStateException;
use Zhortein\DatatableBundle\Sorting\SortCriterion;
use Zhortein\DatatableBundle\State\DatatableState;
use Zhortein\DatatableBundle\State\DatatableStateUrlSerializer;

final class DatatableStateUrlSerializerTest extends TestCase
{
    public function test_it_round_trips_a_versioned_state(): void
    {
        $serializer = new DatatableStateUrlSerializer();
        $state = DatatableState::create(
            page: 4,
            pageSize: 100,
            searchQuery: 'alice',
            sortField: 'email',
            sortDirection: 'desc',
            filters: ['status' => 'active'],
            advancedFilters: [
                'logic' => 'or',
                'conditions' => [
                    ['field' => 'email', 'operator' => 'contains', 'value' => '@example.test'],
                ],
            ],
            visibleColumns: ['email'],
            hiddenColumns: ['internal'],
            sorts: [
                SortCriterion::create('displayName'),
                SortCriterion::create('email', 'desc'),
            ],
        );

        $restored = $serializer->deserialize($serializer->serialize($state));

        self::assertSame($state->toArray(), $restored->toArray());
    }

    public function test_it_serializes_empty_map_fields_as_json_objects(): void
    {
        $payload = new DatatableStateUrlSerializer()->serialize(DatatableState::create());
        $state = json_decode($payload, false, flags: JSON_THROW_ON_ERROR);

        self::assertInstanceOf(\stdClass::class, $state);
        $values = get_object_vars($state);
        self::assertInstanceOf(\stdClass::class, $values['filters'] ?? null);
        self::assertInstanceOf(\stdClass::class, $values['advancedFilters'] ?? null);
        self::assertSame([], $values['visibleColumns'] ?? null);
        self::assertSame([], $values['hiddenColumns'] ?? null);
        self::assertSame([], $values['sorts'] ?? null);
    }

    public function test_parameter_names_are_isolated_by_datatable_instance_and_context(): void
    {
        $serializer = new DatatableStateUrlSerializer();

        $first = $serializer->createParameterName('users', 'first', 'signed-context-a');

        self::assertSame($first, $serializer->createParameterName('users', 'first', 'signed-context-a'));
        self::assertNotSame($first, $serializer->createParameterName('users', 'second', 'signed-context-a'));
        self::assertNotSame($first, $serializer->createParameterName('users', 'first', 'signed-context-b'));
        self::assertNotSame($first, $serializer->createParameterName('orders', 'first', 'signed-context-a'));
        self::assertMatchesRegularExpression('/^_zd_state\\[[A-Za-z0-9_-]{16}]$/', $first);
    }

    public function test_it_rejects_an_unsupported_version(): void
    {
        $serializer = new DatatableStateUrlSerializer();

        $this->expectException(InvalidDatatableStateException::class);
        $this->expectExceptionMessage('version is unsupported');

        $serializer->deserialize('{"version":2}');
    }

    public function test_it_rejects_invalid_typed_values(): void
    {
        $serializer = new DatatableStateUrlSerializer();

        $this->expectException(InvalidDatatableStateException::class);
        $this->expectExceptionMessage('must be a positive integer');

        $serializer->deserialize('{"version":1,"page":"2"}');
    }

    public function test_it_rejects_oversized_payloads(): void
    {
        $serializer = new DatatableStateUrlSerializer();

        $this->expectException(InvalidDatatableStateException::class);
        $this->expectExceptionMessage('invalid length');

        $serializer->deserialize(str_repeat('x', DatatableStateUrlSerializer::MAX_PAYLOAD_LENGTH + 1));
    }

    public function test_it_accepts_legacy_version_one_state_without_sort_criteria(): void
    {
        $state = new DatatableStateUrlSerializer()->deserialize(
            '{"version":1,"page":1,"pageSize":25,"search":null,"sortField":"email","sortDirection":"desc","filters":{},"advancedFilters":{},"visibleColumns":[],"hiddenColumns":[]}',
        );

        self::assertSame([
            ['field' => 'email', 'direction' => 'desc'],
        ], $state->toArray()['sorts']);
    }

    public function test_it_rejects_too_many_sort_criteria(): void
    {
        $sorts = [];

        for ($index = 0; $index <= SortCriterion::MAX_CRITERIA; ++$index) {
            $sorts[] = ['field' => sprintf('field_%d', $index), 'direction' => 'asc'];
        }

        $payload = json_encode([
            'version' => 1,
            'page' => 1,
            'pageSize' => 25,
            'search' => null,
            'sortField' => null,
            'sortDirection' => 'asc',
            'sorts' => $sorts,
            'filters' => new \stdClass(),
            'advancedFilters' => new \stdClass(),
            'visibleColumns' => [],
            'hiddenColumns' => [],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(InvalidDatatableStateException::class);
        $this->expectExceptionMessage('cannot contain more than 8 sort criteria');

        new DatatableStateUrlSerializer()->deserialize($payload);
    }
}
