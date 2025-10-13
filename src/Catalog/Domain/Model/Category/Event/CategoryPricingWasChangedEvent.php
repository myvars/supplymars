<?php

namespace App\Catalog\Domain\Model\Category\Event;

use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class CategoryPricingWasChangedEvent extends AbstractDomainEvent
{
    public function __construct(
        private readonly CategoryPublicId $id,
        private readonly bool $vatRateChanged,
        private readonly bool $markupChanged,
        private readonly bool $priceModelChanged,
    ) {
        parent::__construct(DomainEventType::CATEGORY_PRICING_WAS_CHANGED);
    }

    public function getId(): CategoryPublicId
    {
        return $this->id;
    }

    public function isVatRateChanged(): bool
    {
        return $this->vatRateChanged;
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
