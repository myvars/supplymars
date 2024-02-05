<?php

namespace App\DataFixtures;

use App\Factory\CategoryFactory;
use App\Factory\ManufacturerFactory;
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

        ManufacturerFactory::createOne([
            'name' => 'Apple',
        ]);

        ManufacturerFactory::createMany(199);

        CategoryFactory::createOne([
            'name' => 'Laptops',
            'markup' => 2000,
            'vatRate' => $vatRate,
            'owner' => $user,
        ]);

        CategoryFactory::createMany(299, [
            'vatRate' => VatRateFactory::random(),
            'owner' => UserFactory::random(),
        ]);

        $manager->flush();
    }
}
