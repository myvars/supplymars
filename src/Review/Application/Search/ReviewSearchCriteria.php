<?php

declare(strict_types=1);

namespace App\Review\Application\Search;

use App\Shared\Application\Search\SearchCriteria;
use Symfony\Component\Validator\Constraints as Assert;

final class ReviewSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'rating', 'status', 'createdAt'];

    public ?string $reviewStatus = null;

    #[Assert\Range(min: 1, max: 1000000)]
    public ?int $productId = null;

    #[Assert\Range(min: 1, max: 1000000)]
    public ?int $customerId = null;

    #[Assert\Range(min: 1, max: 5)]
    public ?int $rating = null;
}
