<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Service\ActiveSourceCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: SupplierProduct::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: SupplierProduct::class)]
class SupplierProductStockUpdater
{
    /** @var SupplierProduct[] */
    private array $changedSupplierProducts = [];

    /** @var Product[] */
    private array $changedProducts = [];

    public function __construct(private readonly ActiveSourceCalculator $activeSourceCalculator)
    {
    }

    public function preUpdate(SupplierProduct $supplierProduct, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->hasChangedField('stock') ||
            $eventArgs->hasChangedField('cost') ||
            $eventArgs->hasChangedField('leadTimeDays') ||
            $eventArgs->hasChangedField('product') ||
            $eventArgs->hasChangedField('isActive'))
        {
            $this->changedSupplierProducts[$supplierProduct->getId()] = $supplierProduct;
        }
    }

    public function postUpdate(SupplierProduct $supplierProduct): void
    {
        if (empty($this->changedSupplierProducts)) {
            unset($this->changedProducts);
            return;
        }

        foreach ($this->changedSupplierProducts as $changedSupplierProduct) {
            if ($product = $changedSupplierProduct->getProduct()) {
                $this->changedProducts[$product->getId()] = $product;
            }

            if ($product = $this->activeSourceCalculator->getProductFromActiveSource($changedSupplierProduct)) {
                $this->changedProducts[$product->getId()] = $product;
            }
        }
        $this->processChangedProducts();
        unset($this->changedSupplierProducts);
    }

    public function processChangedProducts(): void
    {
        if (empty($this->changedProducts)) {
            return;
        }

        foreach ($this->changedProducts as $changedProduct) {
            $this->activeSourceCalculator->recalculateActiveSource($changedProduct, false);
        }
        unset($this->changedProducts);
        $this->activeSourceCalculator->flush();
    }
}
