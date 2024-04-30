<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Service\Product\ActiveSourceCalculator;
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
        if ($eventArgs->hasChangedField('stock')
            || $eventArgs->hasChangedField('cost')
            || $eventArgs->hasChangedField('leadTimeDays')
            || $eventArgs->hasChangedField('product')
            || $eventArgs->hasChangedField('isActive')) {
            $this->setChangedSupplierProduct($supplierProduct);
        }
    }

    public function postUpdate(SupplierProduct $supplierProduct): void
    {
        if (empty($this->changedSupplierProducts)) {
            unset($this->changedProducts);

            return;
        }

        foreach ($this->changedSupplierProducts as $changedSupplierProduct) {
            // If the product is mapped, we will need to recalculate the active source
            if ($product = $changedSupplierProduct->getProduct()) {
                $this->setChangedProduct($product);
            }

            // If the supplierProduct is the activeSource, we will need to recalculate the active source
            if ($product = $this->activeSourceCalculator->getProductFromActiveSource($changedSupplierProduct)) {
                $this->setChangedProduct($product);
            }
        }

        if ($this->changedProducts) {
            $this->activeSourceCalculator->recalculateActiveSourceFromArray($this->changedProducts);
            unset($this->changedProducts);
        }
        unset($this->changedSupplierProducts);
    }

    public function setChangedSupplierProduct(SupplierProduct $supplierProduct): void
    {
        $this->changedSupplierProducts[$supplierProduct->getId()] = $supplierProduct;
    }

    public function getChangedSupplierProducts(): array
    {
        return $this->changedSupplierProducts;
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
