<?php

namespace App\DTO\SearchDto;

interface SearchFilterInterface
{
    public function getSearchParams(): array;

    public function getQueryString(): ?string;

    public function setQueryString(?string $queryString): static;
}
