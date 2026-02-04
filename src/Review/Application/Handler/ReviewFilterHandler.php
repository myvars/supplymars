<?php

namespace App\Review\Application\Handler;

use App\Review\Application\Command\ReviewFilter;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Application\Search\FilterParamBuilder;

final readonly class ReviewFilterHandler
{
    private const string ROUTE = 'app_review_index';

    public function __construct(private FilterParamBuilder $params)
    {
    }

    public function __invoke(ReviewFilter $command): Result
    {
        $extras = [
            'reviewStatus' => $command->reviewStatus,
            'productId' => $command->productId,
            'customerId' => $command->customerId,
            'rating' => $command->rating,
        ];

        return Result::ok(
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: $this->params->build($command, $extras),
            )
        );
    }
}
