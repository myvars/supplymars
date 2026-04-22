<?php

declare(strict_types=1);

namespace App\Pricing\Application\Listener;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Event\SubcategoryPricingWasChangedEvent;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: SubcategoryPricingWasChangedEvent::class)]
final readonly class SubcategoryPricingWasChanged
{
    private const array TARGET_SCOPES = ['SUBCATEGORY', 'CATEGORY'];

    public function __construct(
        private SubcategoryRepository $subcategories,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(SubcategoryPricingWasChangedEvent $event): void
    {
        $subcategory = $this->subcategories->getByPublicId($event->getid());
        if (!$subcategory instanceof Subcategory) {
            return;
        }

        $updated = false;
        foreach ($subcategory->getActiveProducts() as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            if ($event->isMarkupChanged()
                && in_array($product->getActiveMarkupTarget(), self::TARGET_SCOPES, true)) {
                $product->recalculatePrice($this->markupCalculator);
                $updated = true;
                continue;
            }

            if ($event->isPriceModelChanged()
                && in_array($product->getActivePriceModelTarget(), self::TARGET_SCOPES, true)) {
                $product->recalculatePrice($this->markupCalculator);
                $updated = true;
            }
        }

        if ($updated) {
            $this->flusher->flush();
        }
    }
}
