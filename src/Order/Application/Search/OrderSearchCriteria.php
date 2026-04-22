<?php

declare(strict_types=1);

namespace App\Order\Application\Search;

use App\Shared\Application\Search\DateRangeSearchCriteriaInterface;
use App\Shared\Application\Search\SearchCriteria;
use App\Shared\UI\Http\Validation\DateRange;
use Symfony\Component\Validator\Constraints as Assert;

#[DateRange]
final class OrderSearchCriteria extends SearchCriteria implements DateRangeSearchCriteriaInterface
{
    protected const array SORT_OPTIONS = ['id', 'createdAt', 'customer.fullName', 'totalPriceIncVat', 'status'];

    protected const string SORT_DIRECTION_DEFAULT = 'DESC';

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Customer Order Id', min: 1, max: 1000000)]
    public ?int $orderId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Purchase Order Id', min: 1, max: 1000000)]
    public ?int $purchaseOrderId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Customer Id', min: 1, max: 1000000)]
    public ?int $customerId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Product Id', min: 1, max: 1000000)]
    public ?int $productId = null;

    #[Assert\Date]
    public ?string $startDate = null;

    #[Assert\Date]
    public ?string $endDate = null;

    public ?string $orderStatus = null;

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }
}
