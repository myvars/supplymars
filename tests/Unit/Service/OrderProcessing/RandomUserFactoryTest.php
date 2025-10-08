<?php

namespace App\Tests\Unit\Service\OrderProcessing;

use Faker\Generator;
use App\Entity\User;
use App\Service\OrderProcessing\RandomUserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RandomUserFactoryTest extends TestCase
{
    public function testCreateReturnsUserWithExpectedProperties(): void
    {
        // Stub Faker\Generator with required methods and correct method signature
        $faker = new class extends Generator {
            public function unique($reset = false, $maxRetries = 10000) { return $this; }

            public function safeEmail(): string { return 'test@example.com'; }

            public function firstName(): string { return 'John'; }

            public function lastName(): string { return 'Doe'; }

            public function password(): string { return 'plainPassword'; }
        };

        $passwordEncoder = $this->createMock(UserPasswordHasherInterface::class);
        $passwordEncoder->method('hashPassword')->willReturn('hashedPassword');

        $factory = new RandomUserFactory($faker, $passwordEncoder);
        $user = $factory->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('John Doe', $user->getFullName());
        $this->assertFalse($user->isStaff());
        $this->assertTrue($user->isVerified());
        $this->assertSame('hashedPassword', $user->getPassword());
    }
}
