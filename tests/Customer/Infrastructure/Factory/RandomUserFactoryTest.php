<?php

namespace App\Tests\Customer\Infrastructure\Factory;

use App\Customer\Infrastructure\Factory\RandomUserFactory;
use Faker\Factory as FakerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RandomUserFactoryTest extends TestCase
{
    public function testCreateBuildsVerifiedCustomerWithHashedPassword(): void
    {
        $faker = FakerFactory::create();
        $faker->seed(1234);

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-password');

        $factory = new RandomUserFactory($faker, $hasher);

        $user = $factory->create();

        self::assertNotEmpty($user->getFullName());
        self::assertNotEmpty($user->getEmail());
        self::assertTrue($user->isVerified());
        self::assertFalse($user->isStaff());
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertSame('hashed-password', $user->getPassword());
        self::assertNotNull($user->getPublicId()->value());
    }
}
