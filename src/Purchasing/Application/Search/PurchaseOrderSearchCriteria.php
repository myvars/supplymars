<?php

namespace App\Purchasing\Application\Search;

use App\Shared\Application\Search\SearchCriteria;
use App\Shared\UI\Http\Validation\DateRange;
use Symfony\Component\Validator\Constraints as Assert;

#[DateRange]
final class PurchaseOrderSearchCriteria extends SearchCriteria
{
    protected const array SORT_OPTIONS = ['id', 'createdAt', 'order.id', 'totalPriceIncVat', 'status'];

    protected const string SORT_DIRECTION_DEFAULT = 'DESC';

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Purchase Order Id', min: 1, max: 1000000)]
    public ?int $purchaseOrderId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Customer Order Id', min: 1, max: 1000000)]
    public ?int $orderId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Customer Id', min: 1, max: 1000000)]
    public ?int $customerId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Product Id', min: 1, max: 1000000)]
    public ?int $productId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Id', min: 1, max: 1000000)]
    public ?int $supplierId = null;

    #[Assert\Date]
    public ?string $startDate = null;

    #[Assert\Date]
    public ?string $endDate = null;

    public ?string $purchaseOrderStatus = null;
}
