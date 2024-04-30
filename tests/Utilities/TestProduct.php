<?php

namespace App\Tests\Utilities;

use App\Entity\Product;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use App\Story\TestProductStory;
use Doctrine\ORM\EntityManagerInterface;


final class TestProduct
{
    private Product $product;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActiveSourceCalculator $activeSourceCalculator,
        private readonly ProductPriceCalculator $productPriceCalculator,
    ) {
        TestProductStory::load();
        $this->product = $this->entityManager->getRepository(Product::class)->findOneBy(['name' => 'Test Product']);
        $this->activeSourceCalculator->recalculateActiveSource($this->product);
        $this->productPriceCalculator->recalculatePrice($this->product);
    }

    public function create(): Product
    {
        return $this->product;
    }
}