<?php

declare(strict_types=1);

namespace App\Catalog\Application\Handler\Category;

use App\Catalog\Application\Command\Category\CategoryFilter;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Application\Search\FilterParamBuilder;

final readonly class CategoryFilterHandler
{
    private const string ROUTE = 'app_catalog_category_index';

    public function __construct(private FilterParamBuilder $params)
    {
    }

    public function __invoke(CategoryFilter $command): Result
    {
        $extras = [
            'priceModel' => $command->priceModel,
            'managerId' => $command->managerId,
            'vatRateId' => $command->vatRateId,
        ];

        return Result::ok(
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: $this->params->build($command, $extras),
            )
        );
    }
}
