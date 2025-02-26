<?php

namespace App\Tests\Unit\DTO\SearchDto;

use App\DTO\SearchDto\CustomerSearchDto;
use PHPUnit\Framework\TestCase;

class CustomerSearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new CustomerSearchDto();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('fullName');
        $dto->setPage(1);
        $dto->setLimit(5);
        $dto->setSortDirection('asc');

        $this->assertSame('queryString', $dto->getQueryString());
        $this->assertSame('query', $dto->getQuery());
        $this->assertSame('fullName', $dto->getSort());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(5, $dto->getLimit());
        $this->assertSame('asc', $dto->getSortDirection());
    }

    public function testGetSearchParams(): void
    {
        $dto = new CustomerSearchDto();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('fullName');
        $dto->setSortDirection('asc');
        $dto->setPage(1);
        $dto->setLimit(5);

        $this->assertSame([
            'query' => 'query',
            'sort' => 'fullName',
            'sortDirection' => 'asc',
            'page' => 1,
            'limit' => 5,
        ], $dto->getSearchParams());
    }
}