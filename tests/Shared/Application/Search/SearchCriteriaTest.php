<?php

namespace App\Tests\Shared\Application\Search;

use App\Shared\Application\Search\SearchCriteria;
use PHPUnit\Framework\TestCase;

final class SearchCriteriaTest extends TestCase
{
    private TestCriteria $criteria;

    protected function setUp(): void
    {
        $this->criteria = new TestCriteria();
    }

    public function testDefaults(): void
    {
        self::assertNull($this->criteria->getQuery());
        self::assertSame('id', $this->criteria->getSort());
        self::assertSame('ASC', $this->criteria->getSortDirection());
        self::assertSame(1, $this->criteria->getPage());
        self::assertSame(5, $this->criteria->getLimit());
    }

    public function testSetSortValidatesAgainstOptionsAndFallsBack(): void
    {
        $this->criteria->setSort('id');
        self::assertSame('id', $this->criteria->getSort());

        $this->criteria->setSort('unknown');
        self::assertSame('id', $this->criteria->getSort());

        $this->criteria->setSort(null);
        self::assertSame('id', $this->criteria->getSort());
    }

    public function testSetSortDirectionNormalizesAndRestricts(): void
    {
        $this->criteria->setSortDirection('desc');
        self::assertSame('DESC', $this->criteria->getSortDirection());

        $this->criteria->setSortDirection('invalid');
        self::assertSame('ASC', $this->criteria->getSortDirection());

        $this->criteria->setSortDirection(null);
        self::assertSame('ASC', $this->criteria->getSortDirection());
    }

    public function testSetPageBounds(): void
    {
        $this->criteria->setPage(-10);
        self::assertSame(1, $this->criteria->getPage());

        $this->criteria->setPage(0);
        self::assertSame(1, $this->criteria->getPage());

        $this->criteria->setPage(2);
        self::assertSame(2, $this->criteria->getPage());

        $this->criteria->setPage(null);
        self::assertSame(1, $this->criteria->getPage());
    }

    public function testSetLimitBoundsAndMax(): void
    {
        $this->criteria->setLimit(-5);
        self::assertSame(1, $this->criteria->getLimit());

        $this->criteria->setLimit(0);
        self::assertSame(1, $this->criteria->getLimit());

        $this->criteria->setLimit(5);
        self::assertSame(5, $this->criteria->getLimit());

        $this->criteria->setLimit(1000);
        self::assertSame(50, $this->criteria->getLimit());

        $this->criteria->setLimit(null);
        self::assertSame(5, $this->criteria->getLimit());
    }

    public function testQueryCanBeSetNullOrString(): void
    {
        $this->criteria->setQuery(null);
        self::assertNull($this->criteria->getQuery());

        $this->criteria->setQuery('abc');
        self::assertSame('abc', $this->criteria->getQuery());
    }
}

/**
 * Minimal concrete implementation to expose protected defaults.
 */
final class TestCriteria extends SearchCriteria
{
    // Uses defaults from SearchCriteria.
}
