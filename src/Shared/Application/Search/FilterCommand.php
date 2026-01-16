<?php

namespace App\Shared\Application\Search;

readonly class FilterCommand implements SearchCriteriaInterface
{
    public function __construct(
        private ?string $query,
        private string $sort,
        private string $sortDirection,
        private int $page,
        private int $limit,
    ) {
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
