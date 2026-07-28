<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider\Http;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Exception\HttpDataProviderException;
use Zhortein\DatatableBundle\Provider\Http\DefaultHttpResponseMapper;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderCapabilities;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderConfiguration;
use Zhortein\DatatableBundle\Provider\Http\HttpResponseMapping;
use Zhortein\DatatableBundle\Provider\Http\HttpTransportResponse;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class DefaultHttpResponseMapperTest extends TestCase
{
    public function test_it_maps_rows_identifiers_counts_and_cursor_metadata(): void
    {
        $definition = new DatatableDefinition('remote-users')
            ->addColumn('id')
            ->addColumn('displayName')
        ;
        $configuration = new HttpProviderConfiguration(
            endpoint: 'https://api.example.test/users',
            capabilities: new HttpProviderCapabilities(),
            fieldMap: ['displayName' => 'profile.name'],
            responseMapping: new HttpResponseMapping(
                rowsPath: 'data.items',
                identifierPath: 'uuid',
                totalItemsPath: 'meta.total',
                filteredItemsPath: 'meta.filtered',
                nextCursorPath: 'meta.next',
                previousCursorPath: 'meta.previous',
                hasNextPagePath: 'meta.has_next',
            ),
        );
        $response = new HttpTransportResponse(200, json_encode([
            'data' => [
                'items' => [
                    ['id' => 7, 'uuid' => 'user-7', 'profile' => ['name' => 'Alice']],
                ],
            ],
            'meta' => [
                'total' => 50,
                'filtered' => 12,
                'next' => 'cursor-2',
                'previous' => null,
                'has_next' => true,
            ],
        ], \JSON_THROW_ON_ERROR));

        $page = new DefaultHttpResponseMapper()->mapResponse(
            $response,
            $definition,
            DatatableRequest::create(),
            $configuration,
        );

        self::assertSame([['id' => 7, 'displayName' => 'Alice']], $page->getRows());
        self::assertSame(['user-7'], $page->getIdentifiers());
        self::assertSame(50, $page->getTotalItems());
        self::assertSame(12, $page->getFilteredItems());
        self::assertSame('cursor-2', $page->getNextCursor());
        self::assertTrue($page->hasNextPage());
    }

    public function test_it_normalizes_remote_errors_without_exposing_the_payload(): void
    {
        $this->expectException(HttpDataProviderException::class);
        $this->expectExceptionMessage('HTTP status 401');
        $this->expectExceptionMessageMatches('/^(?!.*private-token).*$/s');

        new DefaultHttpResponseMapper()->mapResponse(
            new HttpTransportResponse(401, '{"error":"private-token"}'),
            new DatatableDefinition('remote-users'),
            DatatableRequest::create(),
            new HttpProviderConfiguration(
                endpoint: 'https://api.example.test/users',
                capabilities: new HttpProviderCapabilities(),
            ),
        );
    }

    public function test_it_rejects_malformed_json_without_exposing_the_body(): void
    {
        $this->expectException(HttpDataProviderException::class);
        $this->expectExceptionMessage('malformed JSON');

        new DefaultHttpResponseMapper()->mapResponse(
            new HttpTransportResponse(200, 'secret malformed body'),
            new DatatableDefinition('remote-users'),
            DatatableRequest::create(),
            new HttpProviderConfiguration(
                endpoint: 'https://api.example.test/users',
                capabilities: new HttpProviderCapabilities(),
            ),
        );
    }
}
