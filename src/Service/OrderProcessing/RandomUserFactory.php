<?php

namespace App\Service\OrderProcessing;

use App\Entity\User;
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
        $user = new User();
        $user->setEmail($this->faker->unique()->safeEmail());
        $user->setFullName($this->faker->firstName().' '.$this->faker->lastName());
        $user->setIsStaff(false);
        $user->setIsVerified(true);
        $user->setPassword($this->passwordEncoder->hashPassword($user, $this->faker->password()));

        return $user;
    }
}
