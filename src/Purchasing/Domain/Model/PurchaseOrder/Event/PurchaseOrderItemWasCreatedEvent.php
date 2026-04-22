<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Model\PurchaseOrder\Event;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class PurchaseOrderItemWasCreatedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly PurchaseOrderItemPublicId $id)
    {
        parent::__construct(DomainEventType::PURCHASE_ORDER_ITEM_CREATED);
    }

    public function getId(): PurchaseOrderItemPublicId
    {
        return $this->id;
    }
}
