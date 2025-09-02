<?php

namespace App\Event;

use App\Enum\DomainEventType;
use App\ValueObject\CostChange;
use App\ValueObject\StockChange;
use App\ValueObject\SupplierProductPublicId;

class SupplierProductStockWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly SupplierProductPublicId $publicId,
        private readonly StockChange $stockChange,
        private readonly CostChange $costChange,
    )
    {
        parent::__construct(DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED);
    }

    public function publicId(): SupplierProductPublicId
    {
        return $this->publicId;
    }

    public function stockChange(): StockChange
    {
        return $this->stockChange;
    }

    public function costChange(): CostChange
    {
        return $this->costChange;
    }
}
