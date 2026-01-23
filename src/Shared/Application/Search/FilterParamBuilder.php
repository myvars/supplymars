<?php

namespace App\Shared\Application\Search;

final class FilterParamBuilder
{
    /**
     * Build shared search params from a normalized source.
     *
     * @return array<string, mixed>
     */
    public function base(SearchCriteriaInterface $src): array
    {
        return $this->clean([
            'query' => $src->getQuery(),
            'sort' => $src->getSort(),
            'sortDirection' => $src->getSortDirection(),
            'page' => $src->getPage(),
            'limit' => $src->getLimit(),
        ]);
    }

    /**
     * Build full params from source plus extras. Adds `filter=on` if extras present and $addFilterFlag is true.
     *
     * @param array<string, mixed> $extras
     *
     * @return array<string, mixed>
     */
    public function build(SearchCriteriaInterface $src, array $extras = [], bool $addFilterFlag = true): array
    {
        $base = $this->base($src);
        $extras = $this->clean($extras);

        if ($addFilterFlag && $extras !== []) {
            $extras['filter'] = 'on';
        }

        // Prefer base params for shared keys, then layer extras
        return $base + $extras;
    }

    /**
     * Merge arbitrary param arrays, then clean.
     *
     * @param array<string, mixed> ...$sets
     *
     * @return array<string, mixed>
     */
    public function mergeArrays(array ...$sets): array
    {
        return $this->clean(array_merge(...$sets));
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function clean(array $params): array
    {
        return array_filter(
            $params,
            static fn ($value): bool => $value !== null && $value !== ''
        );
    }
}
