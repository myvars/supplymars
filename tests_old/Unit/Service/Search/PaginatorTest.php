<?php

namespace App\Tests\Unit\Service\Search;

use App\Service\Search\Paginator;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    private Paginator $paginator;

    protected function setUp(): void
    {
        $this->paginator = new Paginator();
    }

    public function testCreatePagination(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $page = 1;
        $limit = 10;

        $pagination = $this->paginator->createPagination($queryBuilder, $page, $limit);

        $this->assertInstanceOf(Pagerfanta::class, $pagination);
        $this->assertSame($page, $pagination->getCurrentPage());
        $this->assertSame($limit, $pagination->getMaxPerPage());
    }
}
