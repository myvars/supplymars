<?php

namespace App\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Product\Product;
use App\Pricing\Domain\Model\VatRate\Event\VatRateWasChangedEvent;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: VatRateWasChangedEvent::class)]
final readonly class VatRateWasChanged
{
    public function __construct(
        private VatRateRepository $vatRates,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(VatRateWasChangedEvent $event): void
    {
        $vatRate = $this->vatRates->getByPublicId($event->getId());
        if (!$vatRate instanceof VatRate) {
            return;
        }

        $updated = false;
        foreach ($vatRate->getCategories() as $category) {
            if (!$category instanceof Category) {
                continue;
            }

            foreach ($category->getProducts() as $product) {
                if (!$product instanceof Product) {
                    continue;
                }

                $product->recalculatePrice($this->markupCalculator);
                $updated = true;
            }
        }

        if ($updated) {
            $this->flusher->flush();
        }
    }
}
