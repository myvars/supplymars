<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Address;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $user = $this->createMock(User::class);

        $address = (new Address())
            ->setFullName('John Doe')
            ->setCompanyName('Acme Corp')
            ->setStreet('123 Main St')
            ->setStreet2('Apt 4B')
            ->setCity('Anytown')
            ->setCounty('Anyshire')
            ->setPostCode('12345')
            ->setCountry('Neverland')
            ->setPhoneNumber('+1234567890')
            ->setEmail('test@example.com')
            ->setCustomer($user)
            ->setIsDefaultShippingAddress(true)
            ->setIsDefaultBillingAddress(false);

        $this->assertEquals('John Doe', $address->getFullName());
        $this->assertEquals('Acme Corp', $address->getCompanyName());
        $this->assertEquals('123 Main St', $address->getStreet());
        $this->assertEquals('Apt 4B', $address->getStreet2());
        $this->assertEquals('Anytown', $address->getCity());
        $this->assertEquals('Anyshire', $address->getCounty());
        $this->assertEquals('12345', $address->getPostCode());
        $this->assertEquals('Neverland', $address->getCountry());
        $this->assertEquals('+1234567890', $address->getPhoneNumber());
        $this->assertEquals('test@example.com', $address->getEmail());
        $this->assertSame($user, $address->getCustomer());
        $this->assertTrue($address->isDefaultShippingAddress());
        $this->assertFalse($address->isDefaultBillingAddress());
    }
}