<?php

namespace App\DataFixtures;

use App\Factory\ManufacturerFactory;
use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne([
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

        VatRateFactory::createOne([
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

        ManufacturerFactory::createMany(29);

        $manager->flush();
    }
}
