<?php

namespace App\Event;

use App\Enum\DomainEventType;
use App\ValueObject\CustomerOrderItemPublicId;

class OrderItemWasCreatedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly CustomerOrderItemPublicId $publicId)
    {
        parent::__construct(DomainEventType::ORDER_ITEM_CREATED);
    }

    public function publicId(): CustomerOrderItemPublicId
    {
        return $this->publicId;
    }
}
