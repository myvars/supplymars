<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

readonly class ProductPriceCalculator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MarkupCalculator       $markupCalculator,
    )
    {
    }

    public function recalculatePrice(
        Product $product,
        bool $flush=true
    ): void
    {
        $prettyPriceIncVat = $this->markupCalculator->calculatePrettyPrice(
            $product->getCost(),
            $product->getActiveMarkup(),
            $product->getCategory()->getVatRate()->getRate(),
            $product->getActivePriceModel()
        );
        $customMarkup = $this->markupCalculator->calculateCustomMarkup(
            $product->getCost(),
            $prettyPriceIncVat,
            $product->getCategory()->getVatRate()->getRate(),
        );
        $newSellPrice = $this->markupCalculator->calculateSellPrice(
            $product->getCost(),
            $customMarkup
        );

        $product->setMarkup($customMarkup);
        $product->setSellPrice($newSellPrice);
        $product->setSellPriceIncVat($prettyPriceIncVat);

        if ($flush) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}