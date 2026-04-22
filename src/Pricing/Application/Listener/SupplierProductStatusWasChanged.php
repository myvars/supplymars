<?php

declare(strict_types=1);

namespace App\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStatusWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: SupplierProductStatusWasChangedEvent::class)]
final readonly class SupplierProductStatusWasChanged
{
    public function __construct(
        private SupplierProductRepository $supplierProducts,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(SupplierProductStatusWasChangedEvent $event): void
    {
        $supplierProduct = $this->supplierProducts->getByPublicId($event->getId());
        if (!$supplierProduct instanceof SupplierProduct) {
            return;
        }

        $mappedProduct = $supplierProduct->getProduct();
        if (!$mappedProduct instanceof Product) {
            return;
        }

        $mappedProduct->recalculateActiveSource($this->markupCalculator);

        $this->flusher->flush();
    }
}
