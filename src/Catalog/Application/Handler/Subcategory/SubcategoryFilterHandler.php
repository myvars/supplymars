<?php

namespace App\Catalog\Application\Handler\Subcategory;

use App\Catalog\Application\Command\Subcategory\SubcategoryFilter;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Application\Search\FilterParamBuilder;

final readonly class SubcategoryFilterHandler
{
    private const string ROUTE = 'app_catalog_subcategory_index';

    public function __construct(private FilterParamBuilder $params)
    {
    }

    public function __invoke(SubcategoryFilter $command): Result
    {
        $extras = [
            'categoryId' => $command->categoryId,
            'priceModel' => $command->priceModel,
            'managerId' => $command->managerId,
        ];

        return Result::ok(
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: $this->params->build($command, $extras),
            )
        );
    }
}
