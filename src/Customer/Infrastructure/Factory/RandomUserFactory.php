<?php

namespace App\Customer\Infrastructure\Factory;

use App\Customer\Domain\Model\User\User;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RandomUserFactory
{
    public function __construct(
        private Generator $faker,
        private UserPasswordHasherInterface $passwordEncoder,
    ) {
    }

    public function create(): User
    {
        $user = User::create(
            fullName: $this->faker->firstName() . ' ' . $this->faker->lastName(),
            email: $this->faker->unique()->numerify('####') . '.' . $this->faker->safeEmail(),
            isStaff: false,
            isVerified: true,
        );
        $user->setPassword(
            $this->passwordEncoder->hashPassword($user, 'password')
        );
        $user->setSimulated(true);

        return $user;
    }
}
