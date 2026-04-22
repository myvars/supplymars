<?php

declare(strict_types=1);

namespace App\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\ProductFilter;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Application\Search\FilterParamBuilder;

final readonly class ProductFilterHandler
{
    private const string ROUTE = 'app_catalog_product_index';

    public function __construct(private FilterParamBuilder $params)
    {
    }

    public function __invoke(ProductFilter $command): Result
    {
        $extras = [
            'mfrPartNumber' => $command->mfrPartNumber,
            'categoryId' => $command->categoryId,
            'subcategoryId' => $command->subcategoryId,
            'manufacturerId' => $command->manufacturerId,
            'inStock' => $command->inStock,
        ];

        return Result::ok(
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: $this->params->build($command, $extras),
            )
        );
    }
}
