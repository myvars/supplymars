<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

interface SearchCriteriaInterface
{
    public function getQuery(): ?string;

    public function getSort(): string;

    public function getSortDirection(): string;

    public function getLimit(): int;

    public function getPage(): int;
}
