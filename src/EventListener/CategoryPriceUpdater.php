<?php

namespace App\EventListener;


use App\Entity\Category;
use App\Entity\Product;
use App\Service\ProductPriceCalculator;
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
        if ($eventArgs->hasChangedField('defaultMarkup')) {
            $products = $category->getProducts();
            foreach ($products as $product) {
                $productMarkup = floatval($product->getDefaultMarkup());
                $subcategoryMarkup = floatval($product->getSubcategory()->getDefaultMarkup());
                if ($productMarkup <= 0 && $subcategoryMarkup <= 0) {
                    $this->changedProducts[$product->getId()] = $product;
                }
            }
        }

        if ($eventArgs->hasChangedField('priceModel')) {
            $products = $category->getProducts();
            foreach ($products as $product) {
                $productPriceModel = $product->getPriceModel()->value;
                if ($productPriceModel === 'NONE') {
                    $this->changedProducts[$product->getId()] = $product;
                }
            }
        }
    }

    public function postUpdate(Category $category): void
    {
        if (empty($this->changedProducts)) {
            return;
        }

        foreach ($this->changedProducts as $product) {
            $this->productPriceCalculator->recalculatePrice($product, false);
        }

        $this->productPriceCalculator->flush();
        unset($this->changedProducts);
    }
}
