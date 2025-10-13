<?php

namespace App\Tests\Shared\UI\Http\FormFlow;

use App\Shared\Application\Search\SearchCriteriaInterface;

final class TestSearchCriteria implements SearchCriteriaInterface
{
    public const PAGE_DEFAULT = 1;

    public function __construct(
        private int $page,
        private int $limit,
        private string $query = '',
        private string $sort = 'id',
        private string $direction = 'asc',
    ) {}

    public function getPage(): int { return $this->page; }
    public function getLimit(): int { return $this->limit; }
    public function getQuery(): string { return $this->query; }
    public function getSort(): string { return $this->sort; }
    public function getSortDirection(): string { return $this->direction; }
}
