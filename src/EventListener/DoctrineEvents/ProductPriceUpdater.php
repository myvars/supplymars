<?php

namespace App\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Service\Product\ProductPriceCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Product::class)]
readonly class ProductPriceUpdater
{
    public function __construct(private ProductPriceCalculator $productPriceCalculator)
    {
    }

    public function preUpdate(Product $product, PreUpdateEventArgs $eventArgs): void
    {
        if (
            $eventArgs->hasChangedField('cost')
            || $eventArgs->hasChangedField('defaultMarkup')
            || $eventArgs->hasChangedField('priceModel')
            || $eventArgs->hasChangedField('subcategory')
            || $eventArgs->hasChangedField('isActive')
        ) {
            $this->productPriceCalculator->recalculatePrice($product, false);
        }
    }
}
