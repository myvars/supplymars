<?php

namespace App\Tests\Unit\DTO\SearchDto;

use App\DTO\SearchDto\ProductSearchDto;
use PHPUnit\Framework\TestCase;

class ProductSearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new ProductSearchDto();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('name');
        $dto->setPage(1);
        $dto->setLimit(5);
        $dto->setSortDirection('asc');
        $dto->setMfrPartNumber('ABC123');
        $dto->setCategoryId(123);
        $dto->setSubcategoryId(456);
        $dto->setManufacturerId(789);
        $dto->setInStock(true);

        $this->assertSame('queryString', $dto->getQueryString());
        $this->assertSame('query', $dto->getQuery());
        $this->assertSame('name', $dto->getSort());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(5, $dto->getLimit());
        $this->assertSame('asc', $dto->getSortDirection());
        $this->assertSame('ABC123', $dto->getMfrPartNumber());
        $this->assertSame(123, $dto->getCategoryId());
        $this->assertSame(456, $dto->getSubcategoryId());
        $this->assertSame(789, $dto->getManufacturerId());
        $this->assertTrue($dto->getInStock());
    }

    public function testGetSearchParams(): void
    {
        $dto = new ProductSearchDto();
        $dto->setMfrPartNumber('ABC123');
        $dto->setCategoryId(123);
        $dto->setSubcategoryId(456);
        $dto->setManufacturerId(789);
        $dto->setInStock(true);

        $this->assertSame([
            'mfrPartNumber' => 'ABC123',
            'categoryId' => 123,
            'subcategoryId' => 456,
            'manufacturerId' => 789,
            'inStock' => true,
            'filter' => 'on',
            'query' => null,
            'sort' => null,
            'sortDirection' => null,
            'page' => null,
            'limit' => null,
        ], $dto->getSearchParams());
    }
}