<?php

namespace App\Event;

use App\Entity\CustomerOrder;
use App\Enum\DomainEventType;

class OrderCreatedEvent extends DomainEvent
{
    public const EVENT_TYPE = DomainEventType::ORDER_CREATED;

    public function __construct(private readonly CustomerOrder $customerOrder)
    {
        parent::__construct(self::EVENT_TYPE);
    }

    public function getCustomerOrder(): CustomerOrder
    {
        return $this->customerOrder;
    }
}