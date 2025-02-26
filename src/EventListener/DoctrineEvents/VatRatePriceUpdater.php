<?php

namespace App\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Entity\VatRate;
use App\Service\Product\ProductPriceCalculator;
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
                    $this->setChangedProduct($product);
                }
            }
        }
    }

    public function postUpdate(VatRate $vatRate): void
    {
        if ([] === $this->changedProducts) {
            return;
        }

        $this->productPriceCalculator->recalculatePriceFromArray($this->changedProducts);
        $this->changedProducts = [];
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
