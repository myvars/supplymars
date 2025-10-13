<?php

namespace App\Shared\Application\Search;

abstract class SearchCriteria implements SearchCriteriaInterface
{
    public const string TEMPLATE = 'shared/form_flow/search_filter.html.twig';
    public const int PAGE_DEFAULT = 1;
    public const int LIMIT_MAX = 50;

    protected const int LIMIT_DEFAULT = 5;
    protected const string SORT_DEFAULT = 'id';
    protected const string SORT_DIRECTION_DEFAULT = 'ASC';
    protected const array SORT_OPTIONS = ['id'];
    private const array ALLOWED_SORT_DIRECTIONS = ['ASC', 'DESC'];

    private ?string $query = null;
    private ?string $sort = null;
    private ?string $sortDirection = null;
    private ?int $page = null;
    private ?int $limit = null;

    public function setQuery(?string $query): void
    {
        $this->query = $query;
    }

    public function setSort(?string $sort): void
    {
        if ($sort === null || !in_array($sort, static::SORT_OPTIONS, true)) {
            $sort = static::SORT_DEFAULT;
        }

        $this->sort = $sort;
    }

    public function setSortDirection(?string $sortDirection): void
    {
        $direction = strtoupper((string) $sortDirection);
        if (!in_array($direction, self::ALLOWED_SORT_DIRECTIONS, true)) {
            $direction =static::SORT_DIRECTION_DEFAULT;
        }

        $this->sortDirection = $direction;
    }

    public function setPage(?int $page): void
    {
        if ($page === null) {
            $this->page = null;

            return;
        }
        $this->page = max(static::PAGE_DEFAULT, $page);
    }

    public function setLimit(?int $limit): void
    {
        if ($limit === null) {
            $this->limit = null;

            return;
        }
        $this->limit = max(1, min($limit, static::LIMIT_MAX));
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getSort(): string
    {
        return $this->sort ?? static::SORT_DEFAULT;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection ??static::SORT_DIRECTION_DEFAULT;
    }

    public function getPage(): int
    {
        $value = $this->page ?? static::PAGE_DEFAULT;

        return max(static::PAGE_DEFAULT, $value);
    }

    public function getLimit(): int
    {
        $value = $this->limit ?? static::LIMIT_DEFAULT;

        return min(static::LIMIT_MAX, max(1, $value));
    }
}
