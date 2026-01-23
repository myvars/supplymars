<?php

namespace App\Tests\Customer\Domain;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use PHPUnit\Framework\TestCase;

final class AddressDomainTest extends TestCase
{
    private function stubCustomer(): User
    {
        return $this->createStub(User::class);
    }

    public function testCreateSetsFieldsAndDefaults(): void
    {
        $addr = Address::create(
            fullName: 'Alice Smith',
            companyName: 'Acme Inc',
            street: '123 Main St',
            street2: 'Suite 5',
            city: 'New Town',
            county: 'Region',
            postCode: 'NT-42',
            country: 'Mars Colony',
            phoneNumber: '+123456789',
            email: 'alice@example.com',
            customer: $this->stubCustomer(),
            isDefaultShippingAddress: true,
            isDefaultBillingAddress: false
        );

        self::assertSame('Alice Smith', $addr->getFullName());
        self::assertSame('Acme Inc', $addr->getCompanyName());
        self::assertSame('123 Main St', $addr->getStreet());
        self::assertSame('Suite 5', $addr->getStreet2());
        self::assertSame('New Town', $addr->getCity());
        self::assertSame('Region', $addr->getCounty());
        self::assertSame('NT-42', $addr->getPostCode());
        self::assertSame('Mars Colony', $addr->getCountry());
        self::assertSame('+123456789', $addr->getPhoneNumber());
        self::assertSame('alice@example.com', $addr->getEmail());
        self::assertTrue($addr->isDefaultShippingAddress());
        self::assertFalse($addr->isDefaultBillingAddress());
    }
}
