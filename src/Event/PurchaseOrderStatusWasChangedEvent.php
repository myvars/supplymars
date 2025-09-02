<?php

namespace App\Event;

use App\Enum\DomainEventType;
use App\ValueObject\PurchaseOrderPublicId;
use App\ValueObject\StatusChange;

class PurchaseOrderStatusWasChangedEvent extends AbstractDomainEvent implements StatusWasChangedEventInterface
{
    public function __construct(
        private readonly PurchaseOrderPublicId $publicId,
        private readonly StatusChange $statusChange,
    ) {
        parent::__construct(DomainEventType::PURCHASE_ORDER_STATUS_CHANGED);
    }

    public function publicId(): PurchaseOrderPublicId
    {
        return $this->publicId;
    }

    public function statusChange(): StatusChange
    {
        return $this->statusChange;
    }
}
