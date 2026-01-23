<?php

namespace App\Shared\Infrastructure\Persistence\Search;

use App\Shared\Application\Search\SearchCriteriaInterface;
use Pagerfanta\Adapter\AdapterInterface;

interface FindByCriteriaInterface
{
    /**
     * @return AdapterInterface<mixed>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface;
}
