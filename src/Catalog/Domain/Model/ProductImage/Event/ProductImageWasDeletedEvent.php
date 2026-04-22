<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model\ProductImage\Event;

use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;

final class ProductImageWasDeletedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly ProductPublicId $id,
        public readonly string $imageName,
    ) {
        parent::__construct(DomainEventType::PRODUCT_IMAGE_WAS_DELETED);
    }

    public function getImageName(): string
    {
        return $this->imageName;
    }
}
