<?php

namespace App\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductPricingWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: SupplierProductPricingWasChangedEvent::class)]
final readonly class SupplierProductPricingWasChanged
{
    public function __construct(
        private SupplierProductRepository $supplierProducts,
        private ProductRepository $products,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(SupplierProductPricingWasChangedEvent $event): void
    {
        $supplierProduct = $this->supplierProducts->getByPublicId($event->getId());
        if (!$supplierProduct instanceof SupplierProduct) {
            return;
        }

        $newProduct = $supplierProduct->getProduct();

        $previousProduct = null;
        if ($event->getPreviousMappedProductId() !== null) {
            $previousProduct = $this->products->get($event->getPreviousMappedProductId());
        }

        // No mapping before and no mapping now -> nothing to do
        if ($newProduct === null && $previousProduct === null) {
            return;
        }

        $updated = false;
        if ($previousProduct !== null && $newProduct !== null && $previousProduct->getId() !== $newProduct->getId()) {
            // Move between different products
            $previousProduct->removeSupplierProduct($this->markupCalculator, $supplierProduct);
            $newProduct->addSupplierProduct($this->markupCalculator, $supplierProduct);
            $updated = true;
        } elseif ($previousProduct !== null && $newProduct === null) {
            // Removed mapping
            $previousProduct->removeSupplierProduct($this->markupCalculator, $supplierProduct);
            $updated = true;
        } elseif ($previousProduct === null && $newProduct instanceof Product) {
            // New mapping
            $newProduct->addSupplierProduct($this->markupCalculator, $supplierProduct);
            $updated = true;
        } elseif ($newProduct instanceof Product) {
            // Same mapped product -> just recalc
            $newProduct->recalculateActiveSource($this->markupCalculator);
            $updated = true;
        }

        if ($updated) {
            $this->flusher->flush();
        }
    }
}

