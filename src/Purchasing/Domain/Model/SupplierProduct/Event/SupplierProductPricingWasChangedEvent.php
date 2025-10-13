<?php

namespace App\Purchasing\Domain\Model\SupplierProduct\Event;

use App\Catalog\Domain\Model\Product\ProductId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class SupplierProductPricingWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly SupplierProductPublicId $id,
        private readonly ?ProductId $previousMappedProductId = null,
    ) {
        parent::__construct(DomainEventType::SUPPLIER_PRODUCT_PRICING_WAS_CHANGED);
    }

    public function getId(): SupplierProductPublicId
    {
        return $this->id;
    }

    public function getPreviousMappedProductId(): ?ProductId
    {
        return $this->previousMappedProductId;
    }
}
