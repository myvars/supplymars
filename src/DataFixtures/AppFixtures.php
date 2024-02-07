<?php

namespace App\DataFixtures;

use App\Factory\CategoryFactory;
use App\Factory\ManufacturerFactory;
use App\Factory\ProductFactory;
use App\Factory\SubcategoryFactory;
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
            'rate' => 2000,
        ]);

        VatRateFactory::createOne([
            'name' => 'Reduced rate',
            'rate' => 500,
        ]);

        VatRateFactory::createOne([
            'name' => 'Zero rate',
            'rate' => 0,
        ]);

        $manufacturer = ManufacturerFactory::createOne([
            'name' => 'Apple',
        ]);

        ManufacturerFactory::createMany(199);

        $category = CategoryFactory::createOne([
            'name' => 'Laptops',
            'markup' => 2000,
            'vatRate' => $vatRate,
            'owner' => $user,
        ]);

        CategoryFactory::createMany(29,  function () {
            return [
                'vatRate' => VatRateFactory::random(),
                'owner' => UserFactory::random(),
            ];
        });

        $subcategory = SubcategoryFactory::createOne([
            'name' => 'Macbook Pro',
            'markup' => 5000,
            'category' => $category,
            'vatRate' => $vatRate,
            'owner' => $user,
        ]);

        SubcategoryFactory::createMany(149,  function () {
            return [
                'category' => CategoryFactory::random(),
                'vatRate' => VatRateFactory::random(),
                'owner' => UserFactory::random(),
            ];
        });

        $product = ProductFactory::createOne([
            'name' => 'Macbook Pro 13"',
            'MfrPartNumber' => 'M1MBP132024',
            'category' => $category,
            'subcategory' => $subcategory,
            'manufacturer' => $manufacturer,
            'owner' => $user,
            'vatRate' => $vatRate,
            'cost' => 103500,
            'isActive' => true,
            'leadTimeDays' => 7,
            'sellPrice' => 129900,
            'stock' => 50,
            'weight' => 1388,
            'markup' => 500,
        ]);

        ProductFactory::createMany(299,  function () {
            $randomSubcategory = SubcategoryFactory::random();
            return [
                'subcategory' => $randomSubcategory,
                'category' => $randomSubcategory->getCategory(),
                'manufacturer' => ManufacturerFactory::random(),
                'vatRate' => VatRateFactory::random(),
                'owner' => UserFactory::random(),
            ];
        });

        $manager->flush();
    }
}
