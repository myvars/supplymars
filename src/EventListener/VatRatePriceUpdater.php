<?php

namespace App\EventListener;


use App\Entity\Product;
use App\Entity\VatRate;
use App\Service\ProductPriceCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: VatRate::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: VatRate::class)]
class VatRatePriceUpdater
{
    /** @var Product[] */
    private array $changedProducts = [];

    public function __construct(private readonly ProductPriceCalculator $productPriceCalculator)
    {
    }

    public function preUpdate(VatRate $vatRate, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->hasChangedField('rate')) {
            $categories = $vatRate->getCategories();
            foreach ($categories as $category) {
                $products = $category->getProducts();
                foreach ($products as $product) {
                    $this->changedProducts[$product->getId()] = $product;
                }
            }
        }
    }

    public function postUpdate(VatRate $vatRate): void
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