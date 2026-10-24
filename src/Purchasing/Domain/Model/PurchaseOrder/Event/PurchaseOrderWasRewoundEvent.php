<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Model\PurchaseOrder\Event;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

/**
 * A purchase order and its items were forced back to pending. Not a status change event, so Audit does not log
 * it: the rewind removes the status logs it reverses (PurchaseOrderRewindService).
 */
final class PurchaseOrderWasRewoundEvent extends AbstractDomainEvent
{
    public function __construct(private readonly PurchaseOrderPublicId $id)
    {
        parent::__construct(DomainEventType::PURCHASE_ORDER_REWOUND);
    }

    public function getId(): PurchaseOrderPublicId
    {
        return $this->id;
    }
}
