<?php

namespace App\Event;

use App\Enum\DomainEventType;
use App\ValueObject\PurchaseOrderItemPublicId;
use App\ValueObject\StatusChange;

class PurchaseOrderItemStatusWasChangedEvent extends AbstractDomainEvent implements StatusWasChangedEventInterface
{
    public function __construct(
        private readonly PurchaseOrderItemPublicId $publicId,
        private readonly StatusChange $statusChange
    ) {
        parent::__construct(DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED);
    }

    public function publicId(): PurchaseOrderItemPublicId
    {
        return $this->publicId;
    }

    public function statusChange(): StatusChange
    {
        return $this->statusChange;
    }
}
