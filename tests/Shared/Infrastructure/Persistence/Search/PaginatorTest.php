<?php

namespace App\Tests\Shared\Infrastructure\Persistence\Search;

use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\Paginator;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Exception\LessThan1CurrentPageException;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\TestCase;

final class PaginatorTest extends TestCase
{
    public function testCreatePaginationConfiguresPagerfanta(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getNbResults')->willReturn(10);

        $paginator = new Paginator();
        $pager = $paginator->createPagination($adapter, 2, 5);

        self::assertInstanceOf(Pagerfanta::class, $pager);
        self::assertSame(2, $pager->getCurrentPage());
        self::assertSame(5, $pager->getMaxPerPage());
        self::assertSame(10, $pager->getNbResults());
    }

    public function testSearchPaginationDelegatesToRepositoryAndUsesCriteriaValues(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        // 75 results with limit 25 gives exactly 3 pages, so page 3 is valid.
        $adapter->method('getNbResults')->willReturn(75);

        $criteria = $this->createStub(SearchCriteriaInterface::class);
        $criteria->method('getPage')->willReturn(3);
        $criteria->method('getLimit')->willReturn(25);

        $repository = $this->createMock(FindByCriteriaInterface::class);
        $repository->expects(self::once())
            ->method('findByCriteria')
            ->with($criteria)
            ->willReturn($adapter);

        $paginator = new Paginator();
        $pager = $paginator->searchPagination($repository, $criteria);

        self::assertSame(3, $pager->getCurrentPage());
        self::assertSame(25, $pager->getMaxPerPage());
        self::assertSame(75, $pager->getNbResults());
    }

    public function testCreatePaginationThrowsExceptionForInvalidPage(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getNbResults')->willReturn(10);

        $paginator = new Paginator();

        self::expectException(LessThan1CurrentPageException::class);
        $paginator->createPagination($adapter, page: 0, limit: 5);
    }

    public function testCreatePaginationThrowsExceptionForOutOfRangePage(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getNbResults')->willReturn(10);

        $paginator = new Paginator();

        self::expectException(OutOfRangeCurrentPageException::class);
        $paginator->createPagination($adapter, page: 100, limit: 5);
    }

    public function testCreatePaginationHandlesEmptyResultSet(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getNbResults')->willReturn(0);

        $paginator = new Paginator();
        $pager = $paginator->createPagination($adapter, page: 1, limit: 10);

        self::assertInstanceOf(Pagerfanta::class, $pager);
        self::assertSame(1, $pager->getCurrentPage());
        self::assertSame(0, $pager->getNbResults());
    }
}
