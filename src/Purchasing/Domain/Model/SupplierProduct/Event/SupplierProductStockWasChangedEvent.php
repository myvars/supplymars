<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Model\SupplierProduct\Event;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;

final class SupplierProductStockWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly SupplierProductPublicId $id,
        private readonly StockChange $stockChange,
        private readonly CostChange $costChange,
    ) {
        parent::__construct(DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED);
    }

    public function getId(): SupplierProductPublicId
    {
        return $this->id;
    }

    public function getStockChange(): StockChange
    {
        return $this->stockChange;
    }

    public function getCostChange(): CostChange
    {
        return $this->costChange;
    }
}
