<?php

namespace App\Tests\Pricing\Unit;

use App\Pricing\Application\Search\VatRateSearchCriteria;
use PHPUnit\Framework\TestCase;

class VatRateSearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new VatRateSearchCriteria();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('name');
        $dto->setPage(1);
        $dto->setLimit(5);
        $dto->setSortDirection('asc');

        $this->assertSame('queryString', $dto->getQueryString());
        $this->assertSame('query', $dto->getQuery());
        $this->assertSame('name', $dto->getSort());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(5, $dto->getLimit());
        $this->assertSame('asc', $dto->getSortDirection());
    }

    public function testGetSearchParams(): void
    {
        $dto = new VatRateSearchCriteria();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('name');
        $dto->setSortDirection('asc');
        $dto->setPage(1);
        $dto->setLimit(5);

        $this->assertSame([
            'query' => 'query',
            'sort' => 'name',
            'sortDirection' => 'asc',
            'page' => 1,
            'limit' => 5,
        ], $dto->getSearchParams());
    }
}
