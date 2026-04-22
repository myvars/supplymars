<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Model\PurchaseOrder\Event;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class PurchaseOrderWasCreatedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly PurchaseOrderPublicId $id)
    {
        parent::__construct(DomainEventType::PURCHASE_ORDER_CREATED);
    }

    public function getId(): PurchaseOrderPublicId
    {
        return $this->id;
    }
}
