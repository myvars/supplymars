<?php

namespace App\DTO\SearchDto;

use App\Validator\DateRange;
use Symfony\Component\Validator\Constraints as Assert;

#[DateRange]
final class PurchaseOrderSearchDto extends SearchDto implements SearchFilterInterface
{
    public const string SORT_DEFAULT = 'id';

    public const array SORT_OPTIONS = ['id', 'createdAt', 'customerOrder.id', 'totalPriceIncVat', 'status'];

    public const string SORT_DIRECTION_DEFAULT = 'DESC';

    public const int LIMIT_DEFAULT = 5;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Purchase Order Id', min: 1, max: 1000000)]
    private ?int $purchaseOrderId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Customer Order Id', min: 1, max: 1000000)]
    private ?int $customerOrderId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Customer Id', min: 1, max: 1000000)]
    private ?int $customerId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Product Id', min: 1, max: 1000000)]
    private ?int $productId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Supplier Id', min: 1, max: 1000000)]
    private ?int $supplierId = null;

    #[Assert\Date]
    private ?string $startDate = null;

    #[Assert\Date]
    private ?string $endDate = null;

    private ?string $purchaseOrderStatus = null;

    #[\Override]
    public function getSearchParams(): array
    {
        $searchFilterParams = [
            'purchaseOrderId' => $this->purchaseOrderId,
            'customerOrderId' => $this->customerOrderId,
            'customerId' => $this->customerId,
            'productId' => $this->productId,
            'supplierId' => $this->supplierId,
            'purchaseOrderStatus' => $this->purchaseOrderStatus,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];

        if (array_filter($searchFilterParams)) {
            $searchFilterParams['filter'] = 'on';
        }

        return array_merge($searchFilterParams, parent::getSearchParams());
    }

    public function getPurchaseOrderId(): ?int
    {
        return $this->purchaseOrderId;
    }

    public function setPurchaseOrderId(?int $purchaseOrderId): PurchaseOrderSearchDto
    {
        $this->purchaseOrderId = $purchaseOrderId;

        return $this;
    }

    public function getCustomerOrderId(): ?int
    {
        return $this->customerOrderId;
    }

    public function setCustomerOrderId(?int $customerOrderId): PurchaseOrderSearchDto
    {
        $this->customerOrderId = $customerOrderId;

        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): PurchaseOrderSearchDto
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): PurchaseOrderSearchDto
    {
        $this->productId = $productId;

        return $this;
    }

    public function getSupplierId(): ?int
    {
        return $this->supplierId;
    }

    public function setSupplierId(?int $supplierId): PurchaseOrderSearchDto
    {
        $this->supplierId = $supplierId;

        return $this;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function setStartDate(?string $startDate): PurchaseOrderSearchDto
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function setEndDate(?string $endDate): PurchaseOrderSearchDto
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPurchaseOrderStatus(): ?string
    {
        return $this->purchaseOrderStatus;
    }

    public function setPurchaseOrderStatus(?string $purchaseOrderStatus): PurchaseOrderSearchDto
    {
        $this->purchaseOrderStatus = $purchaseOrderStatus;

        return $this;
    }
}
