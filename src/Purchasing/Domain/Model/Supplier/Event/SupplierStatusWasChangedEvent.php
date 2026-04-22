<?php

declare(strict_types=1);

namespace App\Purchasing\Domain\Model\Supplier\Event;

use App\Purchasing\Domain\Model\Supplier\SupplierPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class SupplierStatusWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly SupplierPublicId $id,
        private readonly bool $activated,
    ) {
        parent::__construct(DomainEventType::SUPPLIER_STATUS_WAS_CHANGED);
    }

    public function getId(): SupplierPublicId
    {
        return $this->id;
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }
}
