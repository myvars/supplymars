<?php

namespace App\Tests\Unit\DTO\SearchDto;

use App\DTO\SearchDto\PurchaseOrderSearchDto;
use PHPUnit\Framework\TestCase;

class PurchaseOrderSearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new PurchaseOrderSearchDto();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('createdAt');
        $dto->setPage(1);
        $dto->setLimit(5);
        $dto->setSortDirection('asc');
        $dto->setPurchaseOrderId(123);
        $dto->setCustomerOrderId(456);
        $dto->setCustomerId(789);
        $dto->setProductId(101);
        $dto->setSupplierId(202);
        $dto->setStartDate('2023-01-01');
        $dto->setEndDate('2023-12-31');
        $dto->setPurchaseOrderStatus('completed');

        $this->assertSame('queryString', $dto->getQueryString());
        $this->assertSame('query', $dto->getQuery());
        $this->assertSame('createdAt', $dto->getSort());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(5, $dto->getLimit());
        $this->assertSame('asc', $dto->getSortDirection());
        $this->assertSame(123, $dto->getPurchaseOrderId());
        $this->assertSame(456, $dto->getCustomerOrderId());
        $this->assertSame(789, $dto->getCustomerId());
        $this->assertSame(101, $dto->getProductId());
        $this->assertSame(202, $dto->getSupplierId());
        $this->assertSame('2023-01-01', $dto->getStartDate());
        $this->assertSame('2023-12-31', $dto->getEndDate());
        $this->assertSame('completed', $dto->getPurchaseOrderStatus());
    }

    public function testGetSearchParams(): void
    {
        $dto = new PurchaseOrderSearchDto();
        $dto->setPurchaseOrderId(123);
        $dto->setCustomerOrderId(456);
        $dto->setCustomerId(789);
        $dto->setProductId(101);
        $dto->setSupplierId(202);
        $dto->setStartDate('2023-01-01');
        $dto->setEndDate('2023-12-31');
        $dto->setPurchaseOrderStatus('completed');

        $this->assertSame([
            'purchaseOrderId' => 123,
            'customerOrderId' => 456,
            'customerId' => 789,
            'productId' => 101,
            'supplierId' => 202,
            'purchaseOrderStatus' => 'completed',
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