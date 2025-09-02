<?php

namespace App\Event;

use App\Enum\DomainEventType;
use App\ValueObject\PurchaseOrderItemPublicId;

class PurchaseOrderItemWasCreatedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly PurchaseOrderItemPublicId $publicId)
    {
        parent::__construct(DomainEventType::PURCHASE_ORDER_ITEM_CREATED);
    }

    public function publicId(): PurchaseOrderItemPublicId
    {
        return $this->publicId;
    }
}
