<?php

namespace App\EventListener;

use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\ActiveSourceCalculator;
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
        if ($eventArgs->hasChangedField('isActive'))
        {
            $supplierProducts = $supplier->getSupplierProducts();

            foreach ($supplierProducts as $supplierProduct) {
                $this->changedSupplierProducts[$supplierProduct->getId()] = $supplierProduct;
            }
        }
    }

    public function postUpdate(Supplier $supplier): void
    {
        if (empty($this->changedSupplierProducts)) {
            return;
        }

        foreach($this->changedSupplierProducts as $changedSupplierProduct) {
            if ($supplier->isIsActive() === false) {
                if ($product = $this->activeSourceCalculator->getProductFromActiveSource($changedSupplierProduct)) {
                    $this->activeSourceCalculator->recalculateActiveSource($product, false);
                }
                continue;
            }

            if ($product = $changedSupplierProduct->getProduct()) {
                $this->activeSourceCalculator->recalculateActiveSource($product, true);
            }
        }
        $this->activeSourceCalculator->flush();
        unset($this->changedSupplierProducts);
    }
}
