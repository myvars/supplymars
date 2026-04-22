<?php

declare(strict_types=1);

namespace App\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Category\Event\CategoryPricingWasChangedEvent;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: CategoryPricingWasChangedEvent::class)]
final readonly class CategoryPricingWasChanged
{
    public function __construct(
        private CategoryRepository $categories,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(CategoryPricingWasChangedEvent $event): void
    {
        $category = $this->categories->getByPublicId($event->getId());
        if (!$category instanceof Category) {
            return;
        }

        $updated = false;
        foreach ($category->getActiveProducts() as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            if ($event->isVatRateChanged()) {
                $product->recalculatePrice($this->markupCalculator);
                $updated = true;
                continue;
            }

            if ($event->isMarkupChanged() && $product->getActiveMarkupTarget() === 'CATEGORY') {
                $product->recalculatePrice($this->markupCalculator);
                $updated = true;
                continue;
            }

            if ($event->isPriceModelChanged() && $product->getActivePriceModelTarget() === 'CATEGORY') {
                $product->recalculatePrice($this->markupCalculator);
                $updated = true;
            }
        }

        if ($updated) {
            $this->flusher->flush();
        }
    }
}
