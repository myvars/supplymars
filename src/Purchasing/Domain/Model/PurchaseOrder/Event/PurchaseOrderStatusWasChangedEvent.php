<?php

namespace App\Purchasing\Domain\Model\PurchaseOrder\Event;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\Event\StatusWasChangedEventInterface;
use App\Shared\Domain\ValueObject\StatusChange;

final class PurchaseOrderStatusWasChangedEvent extends AbstractDomainEvent implements StatusWasChangedEventInterface
{
    public function __construct(
        private readonly PurchaseOrderPublicId $id,
        private readonly StatusChange $statusChange,
    ) {
        parent::__construct(DomainEventType::PURCHASE_ORDER_STATUS_CHANGED);
    }

    public function getId(): PurchaseOrderPublicId
    {
        return $this->id;
    }

    public function getStatusChange(): StatusChange
    {
        return $this->statusChange;
    }
}
