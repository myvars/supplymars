<?php

namespace App\Event;

use App\Enum\DomainEventType;
use App\ValueObject\PurchaseOrderPublicId;

class PurchaseOrderWasCreatedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly PurchaseOrderPublicId $publicId)
    {
        parent::__construct(DomainEventType::PURCHASE_ORDER_CREATED);
    }

    public function publicId(): PurchaseOrderPublicId
    {
        return $this->publicId;
    }
}
