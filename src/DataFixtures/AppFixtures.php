<?php

namespace App\DataFixtures;

use App\Factory\CategoryFactory;
use App\Factory\ManufacturerFactory;
use App\Factory\PriceModelFactory;
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

        $priceModel = PriceModelFactory::createOne([
            'name' => 'None',
            'description' => 'The default price model',
            'modelTag' => 'NONE',
            'isActive' => true,
        ]);

        PriceModelFactory::createOne([
            'name' => 'Pretty 00',
            'description' => 'A pretty price model with .00 rounding',
            'modelTag' => 'PRETTY_00',
            'isActive' => true,
        ]);

        PriceModelFactory::createOne([
            'name' => 'Pretty 10',
            'description' => 'A pretty price model with .10 rounding',
            'modelTag' => 'PRETTY_10',
            'isActive' => true,
        ]);

        PriceModelFactory::createOne([
            'name' => 'Pretty 49',
            'description' => 'A pretty price model with .49 or .99 rounding',
            'modelTag' => 'PRETTY_49',
            'isActive' => true,
        ]);

        PriceModelFactory::createOne([
            'name' => 'Pretty 95',
            'description' => 'A pretty price model with .95 rounding',
            'modelTag' => 'PRETTY_95',
            'isActive' => true,
        ]);

        PriceModelFactory::createOne([
            'name' => 'Pretty 99',
            'description' => 'A pretty price model with .99 rounding',
            'modelTag' => 'PRETTY_99',
            'isActive' => true,
        ]);

        $manufacturer = ManufacturerFactory::createOne([
            'name' => 'Apple',
        ]);

        ManufacturerFactory::createMany(199);

        $category = CategoryFactory::createOne([
            'name' => 'Laptops',
            'defaultMarkup' => 10,
            'vatRate' => $vatRate,
            'owner' => $user,
            'priceModel' => $priceModel,
        ]);

        CategoryFactory::createMany(29,  function () {
            return [
                'vatRate' => VatRateFactory::first(),
                'owner' => UserFactory::random(),
                'priceModel' => rand(1,10) === 1 ? PriceModelFactory::random() :PriceModelFactory::first(),
            ];
        });

        $subcategory = SubcategoryFactory::createOne([
            'name' => 'Macbook Pro',
            'defaultMarkup' => 5,
            'category' => $category,
            'owner' => $user,
            'priceModel' => $priceModel,
        ]);

        SubcategoryFactory::createMany(149,  function () {
            return [
                'category' => CategoryFactory::random(),
                'owner' => UserFactory::random(),
                'priceModel' => rand(1,10) === 1 ? PriceModelFactory::random() :PriceModelFactory::first(),
                'defaultMarkup' => rand(1, 5) === 1 ? rand(1, 10000)/100 : 0
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
            'priceModel' => $priceModel,
        ]);

        ProductFactory::createMany(199,  function () {
            $randomSubcategory = SubcategoryFactory::random();
            return [
                'subcategory' => $randomSubcategory,
                'category' => $randomSubcategory->getCategory(),
                'manufacturer' => ManufacturerFactory::random(),
                'owner' => UserFactory::random(),
                'priceModel' => PriceModelFactory::random(),
                'defaultMarkup' => rand(1, 10) === 1 ? rand(1, 10000)/100 : 0
            ];
        });

        $manager->flush();
    }
}
