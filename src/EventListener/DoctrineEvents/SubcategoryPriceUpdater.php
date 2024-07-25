<?php

namespace App\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Entity\Subcategory;
use App\Service\Product\ProductPriceCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Subcategory::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Subcategory::class)]
class SubcategoryPriceUpdater
{
    /** @var Product[] */
    private array $changedProducts = [];

    public function __construct(private readonly ProductPriceCalculator $productPriceCalculator)
    {
    }

    public function preUpdate(Subcategory $subcategory, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->hasChangedField('defaultMarkup') || $eventArgs->hasChangedField('priceModel')) {
            $products = $subcategory->getActiveProducts();

            foreach ($products as $product) {
                if ($eventArgs->hasChangedField('defaultMarkup')) {
                    if ($product->getActiveMarkupTarget() === 'SUBCATEGORY') {
                        $this->setChangedProduct($product);
                    }
                }

                if ($eventArgs->hasChangedField('priceModel')) {
                    if ($product->getActivePriceModelTarget() === 'SUBCATEGORY') {
                        $this->setChangedProduct($product);
                    }
                }
            }
        }
    }

    public function postUpdate(Subcategory $subcategory): void
    {
        if (empty($this->changedProducts)) {

            return;
        }

        $this->productPriceCalculator->recalculatePriceFromArray($this->changedProducts);
        unset($this->changedProducts);
    }

    public function setChangedProduct(Product $product): void
    {
        $this->changedProducts[$product->getId()] = $product;
    }

    public function getChangedProducts(): array
    {
        return $this->changedProducts;
    }
}