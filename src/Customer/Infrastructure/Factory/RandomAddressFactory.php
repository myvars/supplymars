<?php

namespace App\Customer\Infrastructure\Factory;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\Address\MarsCity;
use App\Customer\Domain\Model\User\User;
use Faker\Generator;

final readonly class RandomAddressFactory
{
    public function __construct(private Generator $faker)
    {
    }

    public function create(
        User $user,
        bool $isShipping = false,
        bool $isBilling = false,
    ): Address {
        $cityData = MarsCity::random();

        return Address::create(
            fullName: $user->getFullName(),
            companyName: 1 === random_int(1, 5) ? $this->faker->company() : null,
            street: $this->faker->streetAddress(),
            street2: 1 === random_int(1, 5) ? $this->faker->streetName() : null,
            city: $cityData->value,
            county: 'Red Zone',
            postCode: $cityData->sectorCode().'-'.random_int(10, 50),
            country: 'Mars Colony',
            phoneNumber: $this->faker->phoneNumber(),
            email: $user->getEmail(),
            customer: $user,
            isDefaultShippingAddress: $isShipping,
            isDefaultBillingAddress: $isBilling,
        );
    }
}
