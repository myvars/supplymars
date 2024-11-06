<?php

namespace App\Service\Sales;

use App\DTO\ProductSalesFilterDto;
use App\Service\Crud\Core\CrudActionInterface;

final readonly class SalesFilter implements CrudActionInterface
{
    public function handle(object $crudOptions): void
    {
        $entity = $crudOptions->getEntity();

        if (!$entity instanceof ProductSalesFilterDto) {
            throw new \InvalidArgumentException('Entity must be an instance of ProductSalesFilterDto');
        }

        $parsedSuccessLink = parse_url($crudOptions->getSuccessLink());
        parse_str($parsedSuccessLink['query'] ?? '', $successLinkParams);
        parse_str($entity->getQueryString() ?? '', $queryStringParams);
        unset($queryStringParams['filter']);

        $mergedQueryString = http_build_query(array_merge(
            $queryStringParams,
            $successLinkParams,
            $entity->getSalesFilterParams()
        ));

        $newSuccessLink = $parsedSuccessLink['path'] . ($mergedQueryString !== '' && $mergedQueryString !== '0' ? '?' . $mergedQueryString : '');

        $crudOptions
            ->setIsUrlRefresh(true)
            ->setSuccessLink($newSuccessLink);
    }
}