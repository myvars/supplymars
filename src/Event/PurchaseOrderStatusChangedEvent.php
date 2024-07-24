<?php

namespace App\Event;

use App\Entity\PurchaseOrder;
use App\Enum\DomainEventType;

class PurchaseOrderStatusChangedEvent extends DomainEvent
{
    public const EVENT_TYPE = DomainEventType::PURCHASE_ORDER_STATUS_CHANGED;

    public function __construct(private readonly PurchaseOrder $purchaseOrder)
    {
        parent::__construct(self::EVENT_TYPE);
    }

    public function getPurchaseOrder(): PurchaseOrder
    {
        return $this->purchaseOrder;
    }
}