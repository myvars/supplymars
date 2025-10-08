<?php

namespace App\Tests\Unit\Service\OrderProcessing;

use App\Entity\Address;
use App\Entity\User;
use App\Service\OrderProcessing\RandomAddressFactory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

class RandomAddressFactoryTest extends TestCase
{
    public function testCreateReturnsAddressWithExpectedProperties(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('user@example.com');
        $user->method('getFullName')->willReturn('John Doe');

        $faker = new class extends Generator {
            public function streetAddress(): string { return '123 Mars St'; }

            public function phoneNumber(): string { return '555-1234'; }

            public function company(): string { return 'Mars Corp'; }

            public function streetName(): string { return 'Red Dune Ave'; }
        };

        $factory = new RandomAddressFactory($faker);
        $address = $factory->create($user);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame('Mars Colony', $address->getCountry());
        $this->assertSame('Red Zone', $address->getCounty());
        $this->assertSame($user, $address->getCustomer());
        $this->assertSame('123 Mars St', $address->getStreet());
        $this->assertSame('555-1234', $address->getPhoneNumber());
        $this->assertSame('user@example.com', $address->getEmail());
        $this->assertSame('John Doe', $address->getFullName());
        $this->assertFalse($address->isDefaultShippingAddress());
        $this->assertFalse($address->isDefaultBillingAddress());
    }
}
