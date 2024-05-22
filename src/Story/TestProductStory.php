<?php

namespace App\Story;

use App\Entity\PriceModel;
use App\Factory\CategoryFactory;
use App\Factory\ManufacturerFactory;
use App\Factory\ProductFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use Zenstruck\Foundry\Story;

final class TestProductStory extends Story
{
    public function __construct(
        private readonly ProductPriceCalculator $productPriceCalculator,
        private readonly ActiveSourceCalculator $activeSourceCalculator,
    ) {
    }

    public function build(): void
    {
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->object();

        $vatRate = VatRateFactory::createOne([
            'name' => 'Test Vat Rate',
            'rate' => '20.000'
        ])->object();

        $category = CategoryFactory::createOne([
            'name' => 'Test Category',
            'vatRate' => $vatRate,
            'owner' => $owner,
            'isActive' => true
        ])->object();

        $subCategory = SubcategoryFactory::createOne([
            'name' => 'Test Subcategory',
            'category' => $category,
            'owner' => $owner,
            'isActive' => true,
        ])->object();

        $manufacturer = ManufacturerFactory::createOne([
            'name' => 'Test Manufacturer',
            'isActive' => true
        ])->object();

        $product = ProductFactory::createOne([
            'name' => 'Test Product',
            'category' => $category,
            'subcategory' => $subCategory,
            'manufacturer' => $manufacturer,
            'owner' => $owner,
            'isActive' => true,
        ])->object();

        $supplier = SupplierFactory::createOne([
            'name' => 'Test Supplier',
            'isActive' => true
        ])->object();

        SupplierProductFactory::createOne([
            'name' => 'Test Supplier Product',
            'product' => $product,
            'supplier' => $supplier,
            'cost' => '100.00',
            'stock' => 10,
            'isActive' => true,
        ])->object();

        $this->activeSourceCalculator->recalculateActiveSource($product);
        $this->productPriceCalculator->recalculatePrice($product);
    }
}
