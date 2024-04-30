<?php

namespace App\Service\Crud\Core;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;

class CrudIndexOptions
{
    public const SORT_DEFAULT = 'id';
    public const SORT_OPTIONS = [self::SORT_DEFAULT];
    public const SORT_DIRECTION_DEFAULT = 'ASC';
    public const LIMIT_DEFAULT = 5;
    public const PAGE_DEFAULT = 1;
    public const QUERY_BUILDER_METHOD = 'findBySearchQueryBuilder';

    private string $section;
    private ?string $query = null;
    private array $sortOptions = self::SORT_OPTIONS;
    private ?string $sort = null;
    private string $sortDefault = self::SORT_DEFAULT;
    private ?string $sortDirection = null;
    private string $sortDirectionDefault = self::SORT_DIRECTION_DEFAULT;
    private int $limit = self::LIMIT_DEFAULT;
    private int $page = self::PAGE_DEFAULT;
    private ServiceEntityRepositoryInterface $repository;

    public function getSection(): string
    {
        return $this->section;
    }

    public function setSection(string $section): CrudIndexOptions
    {
        $this->section = $section;
        return $this;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): CrudIndexOptions
    {
        $this->query = $query;

        return $this;
    }

    public function getSortOptions(): array
    {
        return $this->sortOptions;
    }

    public function setSortOptions(array $sortOptions): CrudIndexOptions
    {
        $this->sortOptions = $sortOptions;

        return $this;
    }

    public function getSort(): ?string
    {
        return $this->sort;
    }

    public function setSort(?string $sort): CrudIndexOptions
    {
        if ($sort && !in_array($sort, $this->sortOptions)) {
            throw new \InvalidArgumentException('Invalid sort option');
        }
        $this->sort = $sort;

        return $this;
    }

    public function getSortDefault(): string
    {
        return $this->sortDefault;
    }

    public function setSortDefault(string $sortDefault): CrudIndexOptions
    {
        $this->sortDefault = $sortDefault;
        return $this;
    }

    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }

    public function setSortDirection(?string $sortDirection): CrudIndexOptions
    {
        $this->sortDirection = $sortDirection;

        return $this;
    }

    public function getSortDirectionDefault(): string
    {
        return $this->sortDirectionDefault;
    }

    public function setSortDirectionDefault(string $sortDirectionDefault): CrudIndexOptions
    {
        $this->sortDirectionDefault = $sortDirectionDefault;
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): CrudIndexOptions
    {
        $this->limit = $limit;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): CrudIndexOptions
    {
        $this->page = $page;

        return $this;
    }

    public function getRepository(): ServiceEntityRepositoryInterface
    {
        return $this->repository;
    }

    public function setRepository(ServiceEntityRepositoryInterface $repository): CrudIndexOptions
    {
        if (!method_exists($repository, self::QUERY_BUILDER_METHOD)) {
            throw new \InvalidArgumentException(
                self::QUERY_BUILDER_METHOD.' method not found in '.$repository::class.' repository'
            );
        }
        $this->repository = $repository;

        return $this;
    }

    public static function create(): static
    {
        // return a new instance of the class
        return new static();
    }
}