<?php

namespace App\Tests\Reporting\Unit;

use App\Reporting\Application\Search\OverdueOrderSearchCriteria;
use App\Reporting\Domain\Metric\SalesDuration;
use PHPUnit\Framework\TestCase;

class OverdueOrderSearchDtoTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $dto = new OverdueOrderSearchCriteria();
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
        $dto = new OverdueOrderSearchCriteria();
        $dto->setDuration('INVALID_DURATION');

        $this->assertSame(SalesDuration::default(), $dto->getDuration());
    }
}
