<?php

namespace App\Event;

use App\Enum\DomainEventType;
use App\ValueObject\CustomerOrderPublicId;

class OrderWasCreatedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly CustomerOrderPublicId $publicId)
    {
        parent::__construct(DomainEventType::ORDER_CREATED);
    }

    public function publicId(): CustomerOrderPublicId
    {
        return $this->publicId;
    }
}
