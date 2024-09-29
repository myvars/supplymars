<?php

namespace App\Event;

use App\Entity\SupplierProduct;
use App\Enum\DomainEventType;

class SupplierProductCostChangedEvent extends DomainEvent
{
    public const EVENT_TYPE = DomainEventType::SUPPLIER_PRODUCT_COST_CHANGED;

    public function __construct(private readonly SupplierProduct $supplierProduct)
    {
        parent::__construct(self::EVENT_TYPE);
    }

    public function getSupplierProduct(): SupplierProduct
    {
        return $this->supplierProduct;
    }
}