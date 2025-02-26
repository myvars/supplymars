<?php

namespace App\Tests\Unit\DTO\SearchDto;

use App\DTO\SearchDto\CategorySearchDto;
use App\Enum\PriceModel;
use PHPUnit\Framework\TestCase;

class CategorySearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new CategorySearchDto();
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

    public function testSetPriceModel(): void
    {
        $dto = new CategorySearchDto();
        $dto->setPriceModel(PriceModel::PRETTY_99->value);

        $this->assertSame(PriceModel::PRETTY_99->value, $dto->getPriceModel());
    }

    public function testSetManagerId(): void
    {
        $dto = new CategorySearchDto();
        $dto->setManagerId(123);

        $this->assertSame(123, $dto->getManagerId());
    }

    public function testSetVatRateId(): void
    {
        $dto = new CategorySearchDto();
        $dto->setVatRateId(456);

        $this->assertSame(456, $dto->getVatRateId());
    }

    public function testGetSearchParams(): void
    {
        $dto = new CategorySearchDto();
        $dto->setPriceModel(PriceModel::PRETTY_99->value);
        $dto->setManagerId(123);
        $dto->setVatRateId(456);

        $this->assertSame([
            'priceModel' => PriceModel::PRETTY_99->value,
            'managerId' => 123,
            'vatRateId' => 456,
            'filter' => 'on',
            'query' => null,
            'sort' => null,
            'sortDirection' => null,
            'page' => null,
            'limit' => null,
        ], $dto->getSearchParams());
    }
}