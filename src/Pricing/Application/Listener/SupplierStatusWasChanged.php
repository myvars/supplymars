<?php

namespace App\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\Supplier\Event\SupplierStatusWasChangedEvent;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: SupplierStatusWasChangedEvent::class)]
final readonly class SupplierStatusWasChanged
{
    public function __construct(
        private SupplierRepository $suppliers,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(SupplierStatusWasChangedEvent $event): void
    {
        $supplier = $this->suppliers->getByPublicId($event->getId());
        if (!$supplier instanceof Supplier) {
            return;
        }

        $updated = false;
        foreach ($supplier->getSupplierProducts() as $supplierProduct) {
            // If the supplier product is not mapped to a product, skip it
            $product = $supplierProduct->getProduct();
            if (!$product instanceof Product) {
                continue;
            }

            // If the supplier is reactivated, recalculate the active source for all mapped products
            if ($event->isActivated()) {
                $product->recalculateActiveSource($this->markupCalculator);
                $updated = true;
                continue;
            }

            // If the supplier is deactivated, only recalculate where the mapped product was the active source
            if ($supplierProduct->getId() === $product->getActiveProductSource()?->getId()) {
                $product->recalculateActiveSource($this->markupCalculator);
                $updated = true;
            }
        }

        if ($updated) {
            $this->flusher->flush();
        }
    }
}
