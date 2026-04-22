<?php

declare(strict_types=1);

namespace App\Order\Domain\Model\Order\Event;

use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class OrderItemWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly OrderItemPublicId $id)
    {
        parent::__construct(DomainEventType::ORDER_ITEM_CREATED);
    }

    public function getId(): OrderItemPublicId
    {
        return $this->id;
    }
}
