<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ProductPriceCalculator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MarkupCalculator $markupCalculator,
        private readonly loggerInterface $logger
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
            $product->getActiveModelTag()
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

        $this->logger->info('Updating product ' . $product->getId() . ' with cost ' . $product->getCost() . ' and active markup ' . $product->getActiveMarkup());

        if ($flush) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}