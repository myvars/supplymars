<?php

namespace App\EventListener;


use App\Entity\Product;
use App\Entity\Subcategory;
use App\Service\ProductPriceCalculator;
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
        if ($eventArgs->hasChangedField('defaultMarkup')) {
            $products = $subcategory->getProducts();
            foreach ($products as $product) {
                $productMarkup = floatval($product->getDefaultMarkup());
                if ($productMarkup <= 0) {
                    $this->changedProducts[$product->getId()] = $product;
                }
            }
        }

        if ($eventArgs->hasChangedField('priceModel')) {
            $products = $subcategory->getProducts();
            foreach ($products as $product) {
                $productPriceModel = $product->getPriceModel()->value;
                if ($productPriceModel === 'NONE') {
                    $this->changedProducts[$product->getId()] = $product;
                }
            }
        }
    }

    public function postUpdate(Subcategory $subcategory): void
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