<?php

namespace App\Story;

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
        $owner = UserFactory::new()->staff()->create()->_real();

        $vatRate = VatRateFactory::createOne([
            'name' => 'Test Vat Rate',
            'rate' => '20.000'
        ])->_real();

        $category = CategoryFactory::createOne([
            'name' => 'Test Category',
            'vatRate' => $vatRate,
            'owner' => $owner,
            'isActive' => true
        ])->_real();

        $subCategory = SubcategoryFactory::createOne([
            'name' => 'Test Subcategory',
            'category' => $category,
            'owner' => $owner,
            'isActive' => true,
        ])->_real();

        $manufacturer = ManufacturerFactory::createOne([
            'name' => 'Test Manufacturer',
            'isActive' => true
        ])->_real();

        $product = ProductFactory::createOne([
            'name' => 'Test Product',
            'category' => $category,
            'subcategory' => $subCategory,
            'manufacturer' => $manufacturer,
            'owner' => $owner,
            'isActive' => true,
        ])->_real();

        $supplier = SupplierFactory::createOne([
            'name' => 'Test Supplier',
            'isActive' => true
        ])->_real();

        SupplierProductFactory::createOne([
            'name' => 'Test Supplier Product',
            'product' => $product,
            'supplier' => $supplier,
            'cost' => '100.00',
            'stock' => 10,
            'isActive' => true,
        ])->_real();

        $this->activeSourceCalculator->recalculateActiveSource($product);
        $this->productPriceCalculator->recalculatePrice($product);
    }
}
