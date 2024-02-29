<?php

namespace App\DataFixtures;

use App\Entity\PriceModel;
use App\Factory\CategoryFactory;
use App\Factory\ManufacturerFactory;
use App\Factory\PriceModelFactory;
use App\Factory\ProductFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\SupplierCategoryFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierManufacturerFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierSubcategoryFactory;
use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = UserFactory::createOne([
            'fullName' => 'Adam Ashmore',
            'email' => 'adam@admin.com',
            'password' => 'letmein',
            'isVerified' => true,
            'roles' => ['ROLE_ADMIN'],
        ]);

        UserFactory::createOne([
            'fullName' => 'Adam Ashmore',
            'email' => 'adam@test.com',
            'isVerified' => true,
            'password' => 'letmein',
        ]);

        $vatRate = VatRateFactory::createOne([
            'name' => 'Standard rate',
            'rate' => 20,
        ]);

        VatRateFactory::createOne([
            'name' => 'Reduced rate',
            'rate' => 5,
        ]);

        VatRateFactory::createOne([
            'name' => 'Zero rate',
            'rate' => 0,
        ]);

        //  Suppliers

        $supplier = SupplierFactory::createOne([
            'name' => 'Butterfly Inc',
        ]);

        SupplierFactory::createMany(2);

        $supplierCategory = SupplierCategoryFactory::createOne([
            'name' => 'Laptop computers',
            'supplier' => $supplier,
        ]);

        SupplierCategoryFactory::createMany(29,  function () {
            return [
                'supplier' => SupplierFactory::random(),
            ];
        });

        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'name' => 'Macbook',
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
        ]);

        SupplierSubcategoryFactory::createMany(149,  function () {
            return [
                'supplier' => SupplierFactory::random(),
                'supplierCategory' => SupplierCategoryFactory::random(),
            ];
        });

        $supplierManufacturer = SupplierManufacturerFactory::createOne([
            'name' => 'Apple',
            'supplier' => $supplier,
        ]);

        SupplierManufacturerFactory::createMany(99,  function () {
            return [
                'supplier' => SupplierFactory::random(),
            ];
        });

        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Macbook Pro 13"',
            'MfrPartNumber' => 'M1MBP132024',
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
            'supplierSubcategory' => $supplierSubcategory,
            'supplierManufacturer' => $supplierManufacturer,
            'cost' => '1190.47',
            'isActive' => true,
            'leadTimeDays' => 7,
            'stock' => 50,
            'weight' => 1388,
        ]);

        SupplierProductFactory::createMany(199,  function () {
            $randomSubcategory = SupplierSubcategoryFactory::random();
            return [
                'supplier' => $randomSubcategory->getSupplier(),
                'supplierCategory' => $randomSubcategory->getSupplierCategory(),
                'supplierSubcategory' => $randomSubcategory,
                'supplierManufacturer' => SupplierManufacturerFactory::random(),
            ];
        });

        $manufacturer = ManufacturerFactory::createOne([
            'name' => 'Apple',
        ]);

        ManufacturerFactory::createMany(199);

        $category = CategoryFactory::createOne([
            'name' => 'Laptops',
            'defaultMarkup' => 10,
            'vatRate' => $vatRate,
            'owner' => $user,
            'priceModel' => PriceModel::DEFAULT,
        ]);

        CategoryFactory::createMany(29,  function () {
            return [
                'vatRate' => VatRateFactory::first(),
                'owner' => UserFactory::random(),
                'priceModel' => PriceModel::DEFAULT,
            ];
        });

        $subcategory = SubcategoryFactory::createOne([
            'name' => 'Macbook Pro',
            'defaultMarkup' => 5,
            'category' => $category,
            'owner' => $user,
            'priceModel' => PriceModel::NONE,
        ]);

        SubcategoryFactory::createMany(149,  function () {
            return [
                'category' => CategoryFactory::random(),
                'owner' => UserFactory::random(),
                'priceModel' => PriceModel::NONE,
                'defaultMarkup' => rand(1, 10) === 1 ? rand(1, 10000)/100 : 0
            ];
        });

        $product = ProductFactory::createOne([
            'name' => 'Macbook Pro 13"',
            'MfrPartNumber' => 'M1MBP132024',
            'category' => $category,
            'subcategory' => $subcategory,
            'manufacturer' => $manufacturer,
            'owner' => $user,
            'cost' => '1190.47',
            'isActive' => true,
            'leadTimeDays' => 7,
            'stock' => 50,
            'weight' => 1388,
            'defaultMarkup' => 5,
            'priceModel' => PriceModel::NONE,
        ]);

        ProductFactory::createMany(199,  function () {
            $randomSubcategory = SubcategoryFactory::random();
            return [
                'subcategory' => $randomSubcategory,
                'category' => $randomSubcategory->getCategory(),
                'manufacturer' => ManufacturerFactory::random(),
                'owner' => UserFactory::random(),
                'priceModel' => PriceModel::NONE,
                'defaultMarkup' => rand(1, 10) === 1 ? rand(1, 10000)/100 : 0
            ];
        });

        $manager->flush();
    }
}
