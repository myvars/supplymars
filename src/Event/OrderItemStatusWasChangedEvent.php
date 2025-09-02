<?php

namespace App\Event;

use App\Enum\DomainEventType;
use App\ValueObject\CustomerOrderItemPublicId;
use App\ValueObject\StatusChange;

class OrderItemStatusWasChangedEvent extends AbstractDomainEvent implements StatusWasChangedEventInterface
{
    public function __construct(
        private readonly CustomerOrderItemPublicId $publicId,
        private readonly StatusChange $statusChange
    ) {
        parent::__construct(DomainEventType::ORDER_ITEM_STATUS_CHANGED);
    }

    public function publicId(): CustomerOrderItemPublicId
    {
        return $this->publicId;
    }

    public function statusChange(): StatusChange
    {
        return $this->statusChange;
    }
}
