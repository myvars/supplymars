<?php

namespace App\Event;

use App\Enum\DomainEventType;
use App\ValueObject\CustomerOrderPublicId;
use App\ValueObject\StatusChange;

class OrderStatusWasChangedEvent extends AbstractDomainEvent implements StatusWasChangedEventInterface
{
    public function __construct(
        private readonly CustomerOrderPublicId $publicId,
        private readonly StatusChange $statusChange
    ) {
        parent::__construct(DomainEventType::ORDER_STATUS_CHANGED);
    }

    public function publicId(): CustomerOrderPublicId
    {
        return $this->publicId;
    }

    public function statusChange(): StatusChange
    {
        return $this->statusChange;
    }
}
