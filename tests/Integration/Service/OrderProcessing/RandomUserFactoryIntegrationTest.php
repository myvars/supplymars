<?php

namespace App\Tests\Integration\Service\OrderProcessing;

use App\Entity\User;
use App\Service\OrderProcessing\RandomUserFactory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RandomUserFactoryIntegrationTest extends KernelTestCase
{
    private RandomUserFactory $factory;

    protected function setUp(): void
    {
        self::bootKernel();
        $faker = static::getContainer()->get(Generator::class);
        $passwordEncoder = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->factory = new RandomUserFactory($faker, $passwordEncoder);
    }

    public function testCreatePersistsValidUser(): void
    {
        $user = $this->factory->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotEmpty($user->getEmail());
        $this->assertNotEmpty($user->getFullName());
        $this->assertFalse($user->isStaff());
        $this->assertTrue($user->isVerified());
        $this->assertNotEmpty($user->getPassword());
    }
}
