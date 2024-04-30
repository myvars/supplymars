<?php

namespace App\DataFixtures;

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

        $vatRate = VatRateFactory::createOne([
            'name' => 'Standard rate',
            'rate' => 20,
            'isDefaultVatRate' => true,
        ]);

        VatRateFactory::createOne([
            'name' => 'Reduced rate',
            'rate' => 5,
        ]);

        VatRateFactory::createOne([
            'name' => 'Zero rate',
            'rate' => 0,
        ]);

        $supplier = SupplierFactory::createOne([
            'name' => 'Turtle Inc',
            'isActive' => true,
        ]);

        SupplierFactory::createMany(2);

        $supplierCategory = SupplierCategoryFactory::createOne([
            'name' => 'Laptops',
            'supplier' => $supplier,
        ]);

        SupplierCategoryFactory::createMany(29, function () {
            return [
                'supplier' => SupplierFactory::random(),
            ];
        });

        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'name' => 'Macbook Pro',
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
        ]);

        SupplierSubcategoryFactory::createMany(99, function () {
            return [
                'supplier' => SupplierFactory::random(),
                'supplierCategory' => SupplierCategoryFactory::random(),
            ];
        });

        $supplierManufacturer = SupplierManufacturerFactory::createOne([
            'name' => 'Apple',
            'supplier' => $supplier,
        ]);

        SupplierManufacturerFactory::createMany(99, function () {
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

        SupplierProductFactory::createMany(99, function () {
            $randomSubcategory = SupplierSubcategoryFactory::random();

            return [
                'supplier' => $randomSubcategory->getSupplier(),
                'supplierCategory' => $randomSubcategory->getSupplierCategory(),
                'supplierSubcategory' => $randomSubcategory,
                'supplierManufacturer' => SupplierManufacturerFactory::random(),
                'isActive' => rand(1, 10) > 1,
            ];
        });

        $manager->flush();
    }
}