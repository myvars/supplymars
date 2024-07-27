<?php

namespace App\EventListener\DoctrineEvents;

use App\Entity\Category;
use App\Entity\Product;
use App\Service\Product\ProductPriceCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Category::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Category::class)]
class CategoryPriceUpdater
{
    /** @var Product[] */
    private array $changedProducts = [];

    public function __construct(private readonly ProductPriceCalculator $productPriceCalculator)
    {
    }

    public function preUpdate(Category $category, PreUpdateEventArgs $eventArgs): void
    {
        if (
            $eventArgs->hasChangedField('defaultMarkup')
            || $eventArgs->hasChangedField('priceModel')
            || $eventArgs->hasChangedField('vatRate')
        ) {
            $products = $category->getActiveProducts();
            foreach ($products as $product) {
                if ($eventArgs->hasChangedField('vatRate')) {
                    $this->setChangedProduct($product);

                    continue;
                }

                if ($eventArgs->hasChangedField('defaultMarkup')) {
                    if ($product->getActiveMarkupTarget() === 'CATEGORY') {
                        $this->setChangedProduct($product);

                        continue;
                    }
                }

                if ($eventArgs->hasChangedField('priceModel')) {
                    if ($product->getActivePriceModelTarget() === 'CATEGORY') {
                        $this->setChangedProduct($product);
                    }
                }
            }
        }
    }

    public function postUpdate(Category $category): void
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
