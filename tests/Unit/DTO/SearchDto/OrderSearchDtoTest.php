<?php

namespace App\Tests\Unit\DTO\SearchDto;

use App\DTO\SearchDto\OrderSearchDto;
use PHPUnit\Framework\TestCase;

class OrderSearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new OrderSearchDto();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('createdAt');
        $dto->setPage(1);
        $dto->setLimit(5);
        $dto->setSortDirection('asc');
        $dto->setCustomerOrderId(123);
        $dto->setPurchaseOrderId(456);
        $dto->setCustomerId(789);
        $dto->setProductId(101);
        $dto->setStartDate('2023-01-01');
        $dto->setEndDate('2023-12-31');
        $dto->setOrderStatus('completed');

        $this->assertSame('queryString', $dto->getQueryString());
        $this->assertSame('query', $dto->getQuery());
        $this->assertSame('createdAt', $dto->getSort());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(5, $dto->getLimit());
        $this->assertSame('asc', $dto->getSortDirection());
        $this->assertSame(123, $dto->getCustomerOrderId());
        $this->assertSame(456, $dto->getPurchaseOrderId());
        $this->assertSame(789, $dto->getCustomerId());
        $this->assertSame(101, $dto->getProductId());
        $this->assertSame('2023-01-01', $dto->getStartDate());
        $this->assertSame('2023-12-31', $dto->getEndDate());
        $this->assertSame('completed', $dto->getOrderStatus());
    }

    public function testGetSearchParams(): void
    {
        $dto = new OrderSearchDto();
        $dto->setCustomerOrderId(123);
        $dto->setPurchaseOrderId(456);
        $dto->setCustomerId(789);
        $dto->setProductId(101);
        $dto->setStartDate('2023-01-01');
        $dto->setEndDate('2023-12-31');
        $dto->setOrderStatus('completed');

        $this->assertSame([
            'customerOrderId' => 123,
            'purchaseOrderId' => 456,
            'customerId' => 789,
            'productId' => 101,
            'orderStatus' => 'completed',
            'startDate' => '2023-01-01',
            'endDate' => '2023-12-31',
            'filter' => 'on',
            'query' => null,
            'sort' => null,
            'sortDirection' => null,
            'page' => null,
            'limit' => null,
        ], $dto->getSearchParams());
    }
}