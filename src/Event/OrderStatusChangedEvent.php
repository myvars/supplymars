<?php

namespace App\Event;

use App\Entity\CustomerOrder;
use App\Enum\DomainEventType;

class OrderStatusChangedEvent extends DomainEvent
{
    public const DomainEventType EVENT_TYPE = DomainEventType::ORDER_STATUS_CHANGED;

    public function __construct(private readonly CustomerOrder $customerOrder)
    {
        parent::__construct(self::EVENT_TYPE);
    }

    public function getCustomerOrder(): CustomerOrder
    {
        return $this->customerOrder;
    }
}
