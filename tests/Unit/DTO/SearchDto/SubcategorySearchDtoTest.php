<?php

namespace App\Tests\Unit\DTO\SearchDto;

use App\DTO\SearchDto\SubcategorySearchDto;
use PHPUnit\Framework\TestCase;

class SubcategorySearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new SubcategorySearchDto();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('name');
        $dto->setPage(1);
        $dto->setLimit(5);
        $dto->setSortDirection('asc');
        $dto->setCategoryId(123);
        $dto->setPriceModel('PRETTY_99');
        $dto->setManagerId(456);

        $this->assertSame('queryString', $dto->getQueryString());
        $this->assertSame('query', $dto->getQuery());
        $this->assertSame('name', $dto->getSort());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(5, $dto->getLimit());
        $this->assertSame('asc', $dto->getSortDirection());
        $this->assertSame(123, $dto->getCategoryId());
        $this->assertSame('PRETTY_99', $dto->getPriceModel());
        $this->assertSame(456, $dto->getManagerId());
    }

    public function testGetSearchParams(): void
    {
        $dto = new SubcategorySearchDto();
        $dto->setCategoryId(123);
        $dto->setPriceModel('PRETTY_99');
        $dto->setManagerId(456);

        $this->assertSame([
            'categoryId' => 123,
            'priceModel' => 'PRETTY_99',
            'managerId' => 456,
            'filter' => 'on',
            'query' => null,
            'sort' => null,
            'sortDirection' => null,
            'page' => null,
            'limit' => null,
        ], $dto->getSearchParams());
    }
}