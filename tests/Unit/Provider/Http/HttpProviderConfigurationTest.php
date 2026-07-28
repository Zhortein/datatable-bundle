<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider\Http;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Enum\HttpPaginationStrategy;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderCapabilities;
use Zhortein\DatatableBundle\Provider\Http\HttpProviderConfiguration;

final class HttpProviderConfigurationTest extends TestCase
{
    public function test_it_exposes_explicit_capabilities_and_mappings(): void
    {
        $capabilities = new HttpProviderCapabilities(
            paginationStrategies: [HttpPaginationStrategy::Offset],
            search: true,
            sorting: true,
            simpleFilters: true,
            exports: true,
        );
        $configuration = new HttpProviderConfiguration(
            endpoint: 'https://api.example.test/users',
            capabilities: $capabilities,
            paginationStrategy: HttpPaginationStrategy::Offset,
            method: 'POST',
            parameterNames: ['page_size' => 'per_page'],
            fieldMap: ['displayName' => 'profile.name'],
            operatorMap: ['eq' => 'equals'],
            contextKeys: ['tenant'],
            timeout: 2.5,
            maxAttempts: 3,
        );

        self::assertTrue($capabilities->supportsPagination(HttpPaginationStrategy::Offset));
        self::assertTrue($capabilities->supportsSearch());
        self::assertTrue($capabilities->supportsSorting());
        self::assertTrue($capabilities->supportsSimpleFilters());
        self::assertTrue($capabilities->supportsExports());
        self::assertSame('POST', $configuration->getMethod());
        self::assertSame('per_page', $configuration->getParameterName('page_size', 'page_size'));
        self::assertSame('profile.name', $configuration->mapField('displayName'));
        self::assertSame('equals', $configuration->mapOperator('eq'));
        self::assertSame(['tenant'], $configuration->getContextKeys());
        self::assertSame(2.5, $configuration->getTimeout());
        self::assertSame(3, $configuration->getMaxAttempts());
    }

    public function test_it_rejects_an_undeclared_pagination_strategy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not declared');

        new HttpProviderConfiguration(
            endpoint: 'https://api.example.test/users',
            capabilities: new HttpProviderCapabilities([HttpPaginationStrategy::Page]),
            paginationStrategy: HttpPaginationStrategy::Cursor,
        );
    }
}
