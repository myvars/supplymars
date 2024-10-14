<?php

namespace App\DTO\SearchDto;

abstract class SearchDto implements SearchInterface
{
    private ?string $queryString = null;

    private ?string $query = null;

    private ?string $sort = null;

    private ?string $sortDirection = null;

    private ?int $page = null;

    private ?int $limit = null;

    public function getSearchParams(): array
    {
        return [
            'query' => $this->query,
            'sort' => $this->sort,
            'sortDirection' => $this->sortDirection,
            'page' => $this->page,
            'limit' => $this->limit,
        ];
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function setQueryString(?string $queryString): static
    {
        $this->queryString = $queryString;

        return $this;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function getSort(): ?string
    {
        return $this->sort;
    }

    public function setSort(?string $sort): static
    {
        if (!in_array($sort, static::SORT_OPTIONS)) {
            $sort = static::SORT_DEFAULT;
        }

        $this->sort = $sort;

        return $this;
    }

    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }

    public function setSortDirection(?string $sortDirection): static
    {
        if (!in_array(strtoupper((string) $sortDirection), ['ASC', 'DESC'])) {
            $sortDirection = strtolower(static::SORT_DIRECTION_DEFAULT);
        }

        $this->sortDirection = strtolower((string) $sortDirection);

        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(?int $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }
}