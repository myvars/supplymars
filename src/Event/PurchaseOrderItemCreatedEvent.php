<?php

namespace App\Event;

use App\Entity\PurchaseOrderItem;
use App\Enum\DomainEventType;

class PurchaseOrderItemCreatedEvent extends DomainEvent
{
    public const DomainEventType EVENT_TYPE = DomainEventType::PURCHASE_ORDER_ITEM_CREATED;

    public function __construct(private readonly PurchaseOrderItem $purchaseOrderItem)
    {
        parent::__construct(self::EVENT_TYPE);
    }

    public function getPurchaseOrderItem(): PurchaseOrderItem
    {
        return $this->purchaseOrderItem;
    }
}