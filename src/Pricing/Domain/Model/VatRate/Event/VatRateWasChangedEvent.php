<?php

namespace App\Pricing\Domain\Model\VatRate\Event;

use App\Pricing\Domain\Model\VatRate\VatRatePublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class VatRateWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(private readonly VatRatePublicId $id)
    {
        parent::__construct(DomainEventType::VAT_RATE_WAS_CHANGED);
    }

    public function getId(): VatRatePublicId
    {
        return $this->id;
    }
}
