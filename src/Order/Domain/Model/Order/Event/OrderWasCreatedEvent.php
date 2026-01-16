<?php

namespace App\Order\Domain\Model\Order\Event;

use App\Order\Domain\Model\Order\OrderPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class OrderWasCreatedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly OrderPublicId $id)
    {
        parent::__construct(DomainEventType::ORDER_CREATED);
    }

    public function getId(): OrderPublicId
    {
        return $this->id;
    }
}
