<?php

namespace App\Event;

use App\Entity\PurchaseOrder;
use App\Enum\DomainEventType;

class PurchaseOrderCreatedEvent extends DomainEvent
{
    public const DomainEventType EVENT_TYPE = DomainEventType::PURCHASE_ORDER_CREATED;

    public function __construct(private readonly PurchaseOrder $purchaseOrder)
    {
        parent::__construct(self::EVENT_TYPE);
    }

    public function getPurchaseOrder(): PurchaseOrder
    {
        return $this->purchaseOrder;
    }
}