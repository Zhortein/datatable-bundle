<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Contract\ExportCancellationInterface;
use Zhortein\DatatableBundle\Contract\HttpTransportInterface;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\HttpPaginationStrategy;
use Zhortein\DatatableBundle\Export\ExportRow;
use Zhortein\DatatableBundle\Export\ExportStreamContext;
use Zhortein\DatatableBundle\Provider\DataProviderRegistry;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderCapabilities;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderConfiguration;
use Zhortein\DatatableBundle\Provider\Http\HttpTransportRequest;
use Zhortein\DatatableBundle\Provider\Http\HttpTransportResponse;
use Zhortein\DatatableBundle\Provider\HttpDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class HttpDataProviderTest extends TestCase
{
    public function test_it_maps_a_remote_page_to_a_datatable_result(): void
    {
        $transport = new RecordingHttpTransport([
            $this->response([
                'items' => [
                    ['id' => 1, 'name' => 'Alice'],
                    ['id' => 2, 'name' => 'Bob'],
                ],
                'total' => 8,
            ]),
        ]);
        $provider = new HttpDataProvider($transport);
        $definition = $this->definition(new HttpProviderConfiguration(
            endpoint: 'https://api.example.test/users',
            capabilities: new HttpProviderCapabilities(),
        ));

        $result = $provider->getData($definition, DatatableRequest::create(page: 2, pageSize: 2));

        self::assertSame([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ], $result->getRows());
        self::assertSame(8, $result->getTotalItems());
        self::assertSame([1, 2], $result->getMetadataValue('identifiers'));
        self::assertSame(['page' => 2, 'page_size' => 2], $transport->requests[0]->getQuery());
    }

    public function test_it_streams_cursor_pages_for_explicitly_exportable_apis(): void
    {
        $transport = new RecordingHttpTransport([
            $this->response([
                'items' => [['id' => 1], ['id' => 2]],
                'total' => 3,
                'pagination' => ['next_cursor' => 'cursor-2', 'has_next' => true],
            ]),
            $this->response([
                'items' => [['id' => 3]],
                'total' => 3,
                'pagination' => ['next_cursor' => null, 'has_next' => false],
            ]),
        ]);
        $provider = new HttpDataProvider($transport);
        $configuration = new HttpProviderConfiguration(
            endpoint: 'https://api.example.test/users',
            capabilities: new HttpProviderCapabilities(
                paginationStrategies: [HttpPaginationStrategy::Cursor],
                exports: true,
                exactCounts: true,
            ),
            paginationStrategy: HttpPaginationStrategy::Cursor,
        );
        $definition = $this->definition($configuration);
        $context = new ExportStreamContext(
            batchSize: 2,
            expectedRowCount: 3,
            cancellation: new class implements ExportCancellationInterface {
                public function isCancelled(): bool
                {
                    return false;
                }
            },
        );

        $rows = iterator_to_array($provider->streamExportRows(
            $definition,
            DatatableRequest::create()->withoutPagination(),
            $context,
        ));

        self::assertSame([['id' => 1], ['id' => 2], ['id' => 3]], array_map(
            static fn (ExportRow $row): array => $row->getValues(),
            $rows,
        ));
        self::assertSame(['limit' => 2], $transport->requests[0]->getQuery());
        self::assertSame(['cursor' => 'cursor-2', 'limit' => 1], $transport->requests[1]->getQuery());
    }

    public function test_it_exposes_exact_export_counts_only_when_declared(): void
    {
        $provider = new HttpDataProvider(new RecordingHttpTransport([]));
        $definition = $this->definition(new HttpProviderConfiguration(
            endpoint: 'https://api.example.test/users',
            capabilities: new HttpProviderCapabilities(exports: false),
        ));

        self::assertSame(-1, $provider->countExportRows($definition, DatatableRequest::create()));
    }

    public function test_it_is_selected_only_for_explicit_http_definitions(): void
    {
        $provider = new HttpDataProvider();
        $definition = new DatatableDefinition('remote-users');

        self::assertFalse($provider->supports($definition));

        $definition->setOption(DataProviderRegistry::OPTION_PROVIDER, HttpDataProvider::PROVIDER_NAME);

        self::assertTrue($provider->supports($definition));
    }

    private function definition(HttpProviderConfiguration $configuration): DatatableDefinition
    {
        return (new DatatableDefinition('remote-users'))
            ->addColumn('id')
            ->addColumn('name')
            ->setOption(DataProviderRegistry::OPTION_PROVIDER, HttpDataProvider::PROVIDER_NAME)
            ->setOption(HttpDataProvider::OPTION_CONFIGURATION, $configuration)
        ;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function response(array $payload): HttpTransportResponse
    {
        return new HttpTransportResponse(200, json_encode($payload, \JSON_THROW_ON_ERROR));
    }
}

final class RecordingHttpTransport implements HttpTransportInterface
{
    /**
     * @var list<HttpTransportRequest>
     */
    public array $requests = [];

    /**
     * @param list<HttpTransportResponse> $responses
     */
    public function __construct(
        private array $responses,
    ) {
    }

    public function send(HttpTransportRequest $request): HttpTransportResponse
    {
        $this->requests[] = $request;
        $response = array_shift($this->responses);

        if (!$response instanceof HttpTransportResponse) {
            throw new \LogicException('No recorded HTTP response remains.');
        }

        return $response;
    }
}
