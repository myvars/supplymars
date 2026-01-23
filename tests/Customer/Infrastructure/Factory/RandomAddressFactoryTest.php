<?php

namespace App\Tests\Customer\Infrastructure\Factory;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Customer\Infrastructure\Factory\RandomAddressFactory;
use Faker\Factory as FakerFactory;
use PHPUnit\Framework\TestCase;

final class RandomAddressFactoryTest extends TestCase
{
    public function testCreateBuildsAddressWithUserDataAndFlags(): void
    {
        $faker = FakerFactory::create();
        $faker->seed(1234);

        $user = $this->createStub(User::class);
        $user->method('getFullName')->willReturn('Alice Smith');
        $user->method('getEmail')->willReturn('alice@example.com');

        $factory = new RandomAddressFactory($faker);

        $addr = $factory->create($user, isShipping: true, isBilling: false);

        self::assertInstanceOf(Address::class, $addr);
        self::assertSame('Alice Smith', $addr->getFullName());
        self::assertSame('alice@example.com', $addr->getEmail());
        self::assertNotEmpty($addr->getStreet());
        self::assertNotEmpty($addr->getCity());
        self::assertSame('Mars Colony', $addr->getCountry());
        self::assertNotEmpty($addr->getPostCode());
        self::assertTrue($addr->isDefaultShippingAddress());
        self::assertFalse($addr->isDefaultBillingAddress());
        self::assertSame($user, $addr->getCustomer());
    }
}
