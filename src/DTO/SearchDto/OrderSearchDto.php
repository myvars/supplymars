<?php

namespace App\DTO\SearchDto;

use Symfony\Component\Validator\Constraints as Assert;

final class OrderSearchDto extends SearchDto implements SearchFilterInterface
{
    public const SORT_DEFAULT = 'id';

    public const SORT_OPTIONS = ['id', 'createdAt', 'customer.fullName', 'totalPriceIncVat', 'status'];

    public const SORT_DIRECTION_DEFAULT = 'DESC';

    public const LIMIT_DEFAULT = 5;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Customer Order Id', min: 1, max: 1000000)]
    private ?int $customerOrderId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Purchase Order Id', min: 1, max: 1000000)]
    private ?int $purchaseOrderId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Customer Id', min: 1, max: 1000000)]
    private ?int $customerId = null;

    #[Assert\Range(notInRangeMessage: 'Please enter a valid Product Id', min: 1, max: 1000000)]
    private ?int $productId = null;

    private ?string $orderStatus = null;

    public function getSearchParams(): array
    {
        $searchFilterParams = [
            'customerOrderId' => $this->customerOrderId,
            'purchaseOrderId' => $this->purchaseOrderId,
            'customerId' => $this->customerId,
            'productId' => $this->productId,
            'orderStatus' => $this->orderStatus,
        ];

        if (array_filter($searchFilterParams)) {
            $searchFilterParams['filter'] = 'on';
        }

        return array_merge($searchFilterParams, parent::getSearchParams());
    }

    public function getCustomerOrderId(): ?int
    {
        return $this->customerOrderId;
    }

    public function setCustomerOrderId(?int $customerOrderId): OrderSearchDto
    {
        $this->customerOrderId = $customerOrderId;

        return $this;
    }

    public function getPurchaseOrderId(): ?int
    {
        return $this->purchaseOrderId;
    }

    public function setPurchaseOrderId(?int $purchaseOrderId): OrderSearchDto
    {
        $this->purchaseOrderId = $purchaseOrderId;

        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): OrderSearchDto
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): OrderSearchDto
    {
        $this->productId = $productId;
        return $this;
    }

    public function getOrderStatus(): ?string
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(?string $orderStatus): OrderSearchDto
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }
}
