<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Model\PurchaseOrder\Event;

use App\Order\Domain\Model\Order\OrderPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

/**
 * An item was taken off a purchase order and deleted. Raised by the purchase order, with its customer order's ID,
 * because the purchase order is deleted too when it was its last item.
 */
final class PurchaseOrderItemWasRemovedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly OrderPublicId $id)
    {
        parent::__construct(DomainEventType::PURCHASE_ORDER_ITEM_REMOVED);
    }

    /** The customer order the purchase order belongs to. */
    public function getId(): OrderPublicId
    {
        return $this->id;
    }
}
