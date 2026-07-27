<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Context\DatatableContext;
use Zhortein\DatatableBundle\Definition\ContextFilterValue;
use Zhortein\DatatableBundle\Definition\DatatableDefinition;
use Zhortein\DatatableBundle\Enum\FilterOperator;
use Zhortein\DatatableBundle\Provider\ArrayDataProvider;
use Zhortein\DatatableBundle\Request\DatatableRequest;

final class ArrayDataProviderPermanentFiltersTest extends TestCase
{
    public function test_it_applies_contextual_and_literal_permanent_filters_before_counting(): void
    {
        $definition = $this->createDefinition()
            ->setContext(new DatatableContext(['orderId' => 101]))
            ->addPermanentFilter(
                'orderId',
                FilterOperator::Equals,
                ContextFilterValue::from('orderId'),
            )
            ->addPermanentFilter('quantity', FilterOperator::GreaterThanOrEquals, 2)
        ;

        $result = new ArrayDataProvider()->getData($definition, DatatableRequest::create(pageSize: 10));

        self::assertSame(1, $result->getTotalItems());
        self::assertSame([
            'Mechanical keyboard',
        ], array_column($result->getRows(), 'product'));
    }

    public function test_it_supports_between_and_sql_like_patterns(): void
    {
        $definition = $this->createDefinition()
            ->addPermanentFilter('quantity', FilterOperator::Between, 1, 2)
            ->addPermanentFilter('product', FilterOperator::Like, '%mouse')
        ;

        $result = new ArrayDataProvider()->getData($definition, DatatableRequest::create(pageSize: 10));

        self::assertSame([
            'Wireless mouse',
        ], array_column($result->getRows(), 'product'));
    }

    public function test_it_rejects_a_missing_context_filter_value(): void
    {
        $definition = $this->createDefinition()
            ->addPermanentFilter(
                'orderId',
                FilterOperator::Equals,
                ContextFilterValue::from('missing'),
            )
        ;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The permanent filter for datatable "order-lines" references missing context key "missing".');

        new ArrayDataProvider()->getData($definition, DatatableRequest::create(pageSize: 10));
    }

    private function createDefinition(): DatatableDefinition
    {
        return new DatatableDefinition('order-lines')
            ->setOption(ArrayDataProvider::OPTION_ROWS, [
                ['id' => 1, 'orderId' => 101, 'product' => 'Mechanical keyboard', 'quantity' => 2],
                ['id' => 2, 'orderId' => 101, 'product' => 'Wireless mouse', 'quantity' => 1],
                ['id' => 3, 'orderId' => 102, 'product' => 'External SSD', 'quantity' => 3],
            ])
            ->addColumn('product', label: 'Product')
            ->addColumn('quantity', label: 'Quantity')
        ;
    }
}
