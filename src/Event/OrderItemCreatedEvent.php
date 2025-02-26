<?php

namespace App\Event;

use App\Entity\CustomerOrderItem;
use App\Enum\DomainEventType;

class OrderItemCreatedEvent extends DomainEvent
{
    public const DomainEventType EVENT_TYPE = DomainEventType::ORDER_ITEM_CREATED;

    public function __construct(private readonly CustomerOrderItem $customerOrderItem)
    {
        parent::__construct(self::EVENT_TYPE);
    }

    public function getCustomerOrderItem(): CustomerOrderItem
    {
        return $this->customerOrderItem;
    }
}
