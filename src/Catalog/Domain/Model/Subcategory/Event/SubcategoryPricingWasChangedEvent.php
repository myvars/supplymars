<?php

namespace App\Catalog\Domain\Model\Subcategory\Event;

use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class SubcategoryPricingWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly SubcategoryPublicId $id,
        private readonly bool $markupChanged,
        private readonly bool $priceModelChanged,
    ) {
        parent::__construct(DomainEventType::SUBCATEGORY_PRICING_WAS_CHANGED);
    }

    public function getId(): SubcategoryPublicId
    {
        return $this->id;
    }

    public function isMarkupChanged(): bool
    {
        return $this->markupChanged;
    }

    public function isPriceModelChanged(): bool
    {
        return $this->priceModelChanged;
    }
}
