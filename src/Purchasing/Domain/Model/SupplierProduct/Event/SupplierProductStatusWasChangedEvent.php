<?php

namespace App\Purchasing\Domain\Model\SupplierProduct\Event;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class SupplierProductStatusWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly SupplierProductPublicId $id)
    {
        parent::__construct(DomainEventType::SUPPLIER_PRODUCT_STATUS_WAS_CHANGED);
    }

    public function getId(): SupplierProductPublicId
    {
        return $this->id;
    }
}
