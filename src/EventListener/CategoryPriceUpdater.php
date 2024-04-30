<?php

namespace App\EventListener;

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
        if ($eventArgs->hasChangedField('defaultMarkup')
            || $eventArgs->hasChangedField('priceModel')
            || $eventArgs->hasChangedField('vatRate')) {
            $products = $category->getActiveProducts();
            foreach ($products as $product) {
                if ($eventArgs->hasChangedField('vatRate')) {
                    $this->setChangedProduct($product);
                    continue;
                }

                if ($eventArgs->hasChangedField('defaultMarkup')) {
                    $productMarkup = floatval($product->getDefaultMarkup());
                    $subcategoryMarkup = floatval($product->getSubcategory()->getDefaultMarkup());
                    // If category is driving the ActiveMarkup
                    if ($productMarkup <= 0 && $subcategoryMarkup <= 0) {
                        $this->setChangedProduct($product);
                        continue;
                    }
                }

                if ($eventArgs->hasChangedField('priceModel')) {
                    $productPriceModel = $product->getPriceModel()->value;
                    $subcategoryPriceModel = $product->getSubcategory()->getPriceModel()->value;
                    // If category is driving the ActivePriceModel
                    if ('NONE' === $productPriceModel && 'NONE' === $subcategoryPriceModel) {
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
