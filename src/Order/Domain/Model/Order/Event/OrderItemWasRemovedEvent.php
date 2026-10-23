<?php

declare(strict_types=1);

namespace App\Order\Domain\Model\Order\Event;

use App\Order\Domain\Model\Order\OrderPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

/** An item was taken off the order and deleted. Raised by the order, which outlives it. */
final class OrderItemWasRemovedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly OrderPublicId $id)
    {
        parent::__construct(DomainEventType::ORDER_ITEM_REMOVED);
    }

    /** The order the item was on. */
    public function getId(): OrderPublicId
    {
        return $this->id;
    }
}
