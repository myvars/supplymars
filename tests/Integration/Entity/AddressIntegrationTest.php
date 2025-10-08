<?php

namespace App\Tests\Integration\Entity;

use App\Factory\AddressFactory;
use App\Factory\CategoryFactory;
use App\Factory\CustomerOrderFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class AddressIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidAddress(): void
    {
        $address = AddressFactory::createOne([
            'street' => '123 Main St',
            'city' => 'Anytown',
            'county' => 'Anyshire',
            'postCode' => '12345',
            'country' => 'Neverland',
            'email' => 'test@example.com',
        ]);

        $errors = $this->validator->validate($address);
        $this->assertCount(0, $errors);
    }

    public function testStreetIsRequired(): void
    {
        $address = AddressFactory::createOne(['street' => '']);

        $violations = $this->validator->validate($address);
        $this->assertSame('Please enter a street', $violations[0]->getMessage());
    }

    public function testCityIsRequired(): void
    {
        $address = AddressFactory::createOne(['city' => '']);

        $violations = $this->validator->validate($address);
        $this->assertSame('Please enter a city', $violations[0]->getMessage());
    }

    public function testCountyIsRequired(): void
    {
        $address = AddressFactory::createOne(['county' => '']);

        $violations = $this->validator->validate($address);
        $this->assertSame('Please enter a county', $violations[0]->getMessage());
    }

    public function testPostCodeIsRequired(): void
    {
        $address = AddressFactory::createOne(['postCode' => '']);

        $violations = $this->validator->validate($address);
        $this->assertSame('Please enter a post code', $violations[0]->getMessage());
    }

    public function testCountryIsRequired(): void
    {
        $address = AddressFactory::createOne(['country' => '']);

        $violations = $this->validator->validate($address);
        $this->assertSame('Please enter a country', $violations[0]->getMessage());
    }

    public function testEmailMustBeValid(): void
    {
        $address = AddressFactory::createOne(['email' => 'invalid-email']);

        $violations = $this->validator->validate($address);
        $this->assertSame('Please enter a valid email address', $violations[0]->getMessage());
    }

    public function testAddressPersistence(): void
    {
        $user = UserFactory::createOne();

        $address = AddressFactory::createOne([
            'fullName' => 'John Doe',
            'companyName' => 'Acme Corp',
            'street' => '123 Main St',
            'street2' => 'Apt 4B',
            'city' => 'Anytown',
            'county' => 'Anyshire',
            'postCode' => '12345',
            'country' => 'Neverland',
            'phoneNumber' => '+1234567890',
            'email' => 'test@example.com',
            'customer' => $user,
            'isDefaultShippingAddress' => true,
            'isDefaultBillingAddress' => false,
        ]);

        $persistedAddress = AddressFactory::repository()->find($address->getId())->_real();
        $this->assertEquals('123 Main St', $persistedAddress->getStreet());
    }

    public function testAddCustomerOrderToAddress(): void
    {
        $user = UserFactory::createOne();
        $address = AddressFactory::createOne([
            'customer' => $user,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true
        ])->_real();
        $customerOrder = CustomerOrderFactory::createOne(['customer' => $user, 'billingAddress' => $address])->_real();

        $this->assertTrue($address->getCustomerOrders()->contains($customerOrder));
        $this->assertSame($address, $customerOrder->getBillingAddress());
    }

    public function testRemoveCustomerOrderFromAddress(): void
    {
        $user = UserFactory::createOne();
        $address = AddressFactory::createOne([
            'customer' => $user,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true
        ])->_real();
        $customerOrder = CustomerOrderFactory::createOne(['customer' => $user, 'billingAddress' => $address])->_real();

        $address->removeCustomerOrder($customerOrder);

        $this->assertFalse($address->getCustomerOrders()->contains($customerOrder));
    }

    public function testReAddAddressToUser(): void
    {
        $user = UserFactory::createOne()->_real();
        $address = AddressFactory::createOne(['customer' => $user])->_real();

        $user->removeAddress($address);
        $user->addAddress($address);

        $this->assertTrue($user->getAddresses()->contains($address));
        $this->assertSame($user, $address->getCustomer());
    }

    public function testAddCategoryToUser(): void
    {
        $user = UserFactory::createOne()->_real();
        $category = CategoryFactory::createOne(['owner' => $user])->_real();

        $this->assertTrue($user->getCategories()->contains($category));
        $this->assertSame($user, $category->getOwner());
    }

    public function testRemoveCategoryFromUser(): void
    {
        $user = UserFactory::createOne()->_real();
        $category = CategoryFactory::createOne(['owner' => $user])->_real();

        $user->removeCategory($category);

        $this->assertFalse($user->getCategories()->contains($category));
        $this->assertNull($category->getOwner());
    }

    public function testReAddCategoryToUser(): void
    {
        $user = UserFactory::createOne()->_real();
        $category = CategoryFactory::createOne(['owner' => $user])->_real();

        $user->removeCategory($category);
        $user->addCategory($category);

        $this->assertTrue($user->getCategories()->contains($category));
        $this->assertSame($user, $category->getOwner());
    }
}
