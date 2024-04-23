<?php

namespace App\Service;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class Paginator
{
    public function createPagination(
        ServiceEntityRepositoryInterface $repository,
        ?string $query,
        string $sort,
        string $sortDirection,
        int $limit,
        int $page
    ): Pagerfanta
    {
        if (!method_exists($repository, 'findBySearchQueryBuilder')) {
            throw new \InvalidArgumentException('findBySearchQueryBuilder method not found in repository');
        }

        $queryBuilder = $repository->findBySearchQueryBuilder($query, $sort, $sortDirection);

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($queryBuilder),
            $page,
            $limit
        );
    }
}