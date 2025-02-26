<?php

namespace App\Repository;

use App\DTO\SearchDto\SearchInterface;
use Doctrine\ORM\QueryBuilder;

interface SearchQueryInterface
{
    public function findBySearchDto(SearchInterface $searchDto): QueryBuilder;
}
