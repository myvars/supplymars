<?php

namespace App\Service\Search;

use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class Paginator
{
    public function createPagination(QueryBuilder $queryBuilder, int $page, int $limit): Pagerfanta
    {
        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($queryBuilder),
            $page,
            $limit
        );
    }
}