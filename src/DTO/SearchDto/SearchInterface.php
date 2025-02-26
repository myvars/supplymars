<?php

namespace App\DTO\SearchDto;

interface SearchInterface
{
    public function getQuery(): ?string;

    public function getSort(): ?string;

    public function getSortDirection(): ?string;

    public function getLimit(): ?int;

    public function getPage(): ?int;
}
