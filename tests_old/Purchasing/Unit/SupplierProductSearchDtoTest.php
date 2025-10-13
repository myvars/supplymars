<?php

namespace App\Tests\Purchasing\Unit;

use App\Purchasing\Application\Search\SupplierProductSearchCriteria;
use PHPUnit\Framework\TestCase;

class SupplierProductSearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new SupplierProductSearchCriteria();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('name');
        $dto->setPage(1);
        $dto->setLimit(5);
        $dto->setSortDirection('asc');
        $dto->setSupplierId(123);
        $dto->setProductCode('ABC123');
        $dto->setSupplierCategoryId(456);
        $dto->setSupplierSubcategoryId(789);
        $dto->setSupplierManufacturerId(101);
        $dto->setInStock(true);
        $dto->setIsActive(true);

        $this->assertSame('queryString', $dto->getQueryString());
        $this->assertSame('query', $dto->getQuery());
        $this->assertSame('name', $dto->getSort());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(5, $dto->getLimit());
        $this->assertSame('asc', $dto->getSortDirection());
        $this->assertSame(123, $dto->getSupplierId());
        $this->assertSame('ABC123', $dto->getProductCode());
        $this->assertSame(456, $dto->getSupplierCategoryId());
        $this->assertSame(789, $dto->getSupplierSubcategoryId());
        $this->assertSame(101, $dto->getSupplierManufacturerId());
        $this->assertTrue($dto->getInStock());
        $this->assertTrue($dto->getIsActive());
    }

    public function testGetSearchParams(): void
    {
        $dto = new SupplierProductSearchCriteria();
        $dto->setSupplierId(123);
        $dto->setProductCode('ABC123');
        $dto->setSupplierCategoryId(456);
        $dto->setSupplierSubcategoryId(789);
        $dto->setSupplierManufacturerId(101);
        $dto->setInStock(true);
        $dto->setIsActive(true);

        $this->assertSame([
            'supplierId' => 123,
            'productCode' => 'ABC123',
            'supplierCategoryId' => 456,
            'supplierSubcategoryId' => 789,
            'supplierManufacturerId' => 101,
            'inStock' => true,
            'isActive' => true,
            'filter' => 'on',
            'query' => null,
            'sort' => null,
            'sortDirection' => null,
            'page' => null,
            'limit' => null,
        ], $dto->getSearchParams());
    }
}
