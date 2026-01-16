<?php

namespace App\Reporting\Application\Report;

abstract class ReportCriteria implements ReportCriteriaInterface
{
    use SalesDurationTrait;

    public const int LIMIT_MAX = 50;

    protected const int LIMIT_DEFAULT = 10;

    protected const string SORT_DIRECTION_DEFAULT = 'ASC';

    private const array ALLOWED_SORT_DIRECTIONS = ['ASC', 'DESC'];

    protected ?string $sort = null;

    private ?string $sortDirection = null;

    private ?int $limit = null;

    abstract protected static function defaultSortField(): string;

    abstract public function setSort(?string $sort): void;

    public function setSortDirection(?string $sortDirection): void
    {
        $direction = strtoupper((string) $sortDirection);
        if (!in_array($direction, self::ALLOWED_SORT_DIRECTIONS, true)) {
            $direction = strtoupper(static::SORT_DIRECTION_DEFAULT);
        }

        $this->sortDirection = $direction;
    }

    public function setLimit(?int $limit): void
    {
        if ($limit === null) {
            $this->limit = null;

            return;
        }

        $this->limit = max(1, min($limit, static::LIMIT_MAX));
    }

    public function getSort(): string
    {
        return $this->sort ?? static::defaultSortField();
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection ?? static::SORT_DIRECTION_DEFAULT;
    }

    public function getLimit(): int
    {
        $value = $this->limit ?? static::LIMIT_DEFAULT;

        return min(static::LIMIT_MAX, max(1, $value));
    }
}
