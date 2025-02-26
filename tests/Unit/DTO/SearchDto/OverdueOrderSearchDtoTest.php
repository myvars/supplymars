<?php

namespace App\Tests\Unit\DTO\SearchDto;

use App\DTO\SearchDto\OverdueOrderSearchDto;
use App\Enum\SalesDuration;
use PHPUnit\Framework\TestCase;

class OverdueOrderSearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new OverdueOrderSearchDto();
        $dto->setQueryString('queryString');
        $dto->setQuery('query');
        $dto->setSort('dueDate');
        $dto->setPage(1);
        $dto->setLimit(10);
        $dto->setSortDirection('asc');
        $dto->setDuration(SalesDuration::LAST_7->value);

        $this->assertSame('queryString', $dto->getQueryString());
        $this->assertSame('query', $dto->getQuery());
        $this->assertSame('dueDate', $dto->getSort());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(10, $dto->getLimit());
        $this->assertSame('asc', $dto->getSortDirection());
        $this->assertSame(SalesDuration::LAST_7, $dto->getDuration());
    }

    public function testInvalidSetDuration(): void
    {
        $dto = new OverdueOrderSearchDto();
        $dto->setDuration('INVALID_DURATION');

        $this->assertSame(SalesDuration::default(), $dto->getDuration());
    }
}