<?php

namespace App\Service\OrderProcessing;

use App\Entity\Address;
use App\Entity\User;
use App\Enum\MarsCity;
use Faker\Generator;

final readonly class RandomAddressFactory
{
    public function __construct(
        private Generator $faker,
    ) {
    }

    public function create(User $user): Address
    {
        $cityData = MarsCity::random();

        $address = new Address();
        $address->setCity($cityData->value);
        $address->setCountry('Mars Colony');
        $address->setCounty('Red Zone');
        $address->setCustomer($user);
        $address->setPostCode($cityData->sectorCode().'-'.random_int(10, 50));
        $address->setStreet($this->faker->streetAddress());
        $address->setPhoneNumber($this->faker->phoneNumber());
        $address->setEmail($user->getEmail());
        $address->setFullName($user->getFullName());
        $address->setCompanyName(1 === random_int(1, 5) ? $this->faker->company() : null);
        $address->setStreet2(1 === random_int(1, 5) ? $this->faker->streetName() : null);
        $address->setIsDefaultShippingAddress(false);
        $address->setIsDefaultBillingAddress(false);

        return $address;
    }
}
