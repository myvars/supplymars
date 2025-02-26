<?php

namespace App\Service\Search;

use App\DTO\SearchDto\SearchFilterInterface;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;

final readonly class SearchFilter implements CrudActionInterface
{
    public function handle(CrudOptions $crudOptions): void
    {
        $entity = $crudOptions->getEntity();

        if (!$entity instanceof SearchFilterInterface) {
            throw new \InvalidArgumentException('Entity must implement SearchFilterInterface');
        }

        $parsedSuccessLink = parse_url($crudOptions->getSuccessLink());
        parse_str($parsedSuccessLink['query'] ?? '', $successLinkParams);
        parse_str($entity->getQueryString() ?? '', $queryStringParams);
        unset($queryStringParams['filter']);

        $mergedQueryString = http_build_query(array_merge(
            $queryStringParams,
            $successLinkParams,
            $entity->getSearchParams()
        ));

        $newSuccessLink = $parsedSuccessLink['path']
            .('' !== $mergedQueryString && '0' !== $mergedQueryString ? '?'.$mergedQueryString : '');

        $crudOptions
            ->setIsUrlRefresh(true)
            ->setSuccessLink($newSuccessLink);
    }
}
