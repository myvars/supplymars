<?php

namespace App\EventListener\DoctrineEvents;

use App\Entity\Product;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\Product\ActiveSourceCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Supplier::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Supplier::class)]
class SupplierStockUpdater
{
    /** @var SupplierProduct[] */
    private array $changedSupplierProducts = [];

    public function __construct(private readonly ActiveSourceCalculator $activeSourceCalculator)
    {
    }

    public function preUpdate(Supplier $supplier, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->hasChangedField('isActive')) {
            $supplierProducts = $supplier->getSupplierProducts();

            foreach ($supplierProducts as $supplierProduct) {
                $this->setChangedSupplierProduct($supplierProduct);
            }
        }
    }

    public function postUpdate(Supplier $supplier): void
    {
        if ([] === $this->changedSupplierProducts) {
            return;
        }

        foreach ($this->changedSupplierProducts as $changedSupplierProduct) {
            // If supplier becomes inactive
            if (false === $supplier->isActive()) {
                // Get product from ActiveSource
                $product = $this->activeSourceCalculator->getProductFromActiveSource($changedSupplierProduct);
                if ($product instanceof Product) {
                    $this->activeSourceCalculator->recalculateActiveSource($product, false);
                }

                continue;
            }

            // Get mapped product from supplierProduct
            if ($product = $changedSupplierProduct->getProduct()) {
                $this->activeSourceCalculator->recalculateActiveSource($product, true);
            }
        }

        $this->activeSourceCalculator->flush();
        $this->changedSupplierProducts = [];
    }

    public function setChangedSupplierProduct(SupplierProduct $supplierProduct): void
    {
        $this->changedSupplierProducts[$supplierProduct->getId()] = $supplierProduct;
    }

    public function getChangedSupplierProducts(): array
    {
        return $this->changedSupplierProducts;
    }
}
