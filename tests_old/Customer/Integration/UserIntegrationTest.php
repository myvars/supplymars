<?php

namespace App\Tests\Customer\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\AddressFactory;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\StatusChangeLogFactory;
use tests\Shared\Factory\SubcategoryFactory;
use tests\Shared\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;

class UserIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidUser(): void
    {
        $user = UserFactory::createOne([
            'fullName' => 'Valid User',
            'email' => 'valid@example.com',
            'isStaff' => true,
            'isVerified' => true,
            'password' => 'validpassword',
        ]);

        $errors = $this->validator->validate($user);
        $this->assertCount(0, $errors);
    }

    public function testInvalidEmail(): void
    {
        $user = UserFactory::createOne(['email' => 'invalid-email']);

        $errors = $this->validator->validate($user);
        $this->assertSame('This value is not a valid email address.', $errors[0]->getMessage());
    }

    public function testBlankFullName(): void
    {
        $user = UserFactory::createOne(['fullName' => '']);

        $errors = $this->validator->validate($user);
        $this->assertSame('Please enter a full name', $errors[0]->getMessage());
    }

    public function testStaffUser(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $persistedUser = UserFactory::repository()->find($user->getId());
        $this->assertTrue($persistedUser->isStaff());
        $this->assertEquals('Staff Member', $persistedUser->getFullName());
        $this->assertContains('ROLE_ADMIN', $persistedUser->getRoles());
    }

    public function testUserPersistence(): void
    {
        $user = UserFactory::createOne([
            'fullName' => 'John Doe',
            'email' => 'test@example.com',
            'isStaff' => false,
            'isVerified' => true,
            'password' => 'password123',
        ]);

        $persistedUser = UserFactory::repository()->find($user->getId());
        $this->assertEquals('test@example.com', $persistedUser->getEmail());
    }

    public function testAddCategoryToUser(): void
    {
        $user = UserFactory::createOne();
        $category = CategoryFactory::createOne(['owner' => $user]);

        $this->assertTrue($user->getCategories()->contains($category));
        $this->assertSame($user, $category->getOwner());
    }

    public function testRemoveCategoryFromUser(): void
    {
        $user = UserFactory::createOne();
        $category = CategoryFactory::createOne(['owner' => $user]);

        $user->removeCategory($category);

        $this->assertFalse($user->getCategories()->contains($category));
        $this->assertNull($category->getOwner());
    }

    public function testReAddCategoryToUser(): void
    {
        $user = UserFactory::createOne();
        $category = CategoryFactory::createOne(['owner' => $user]);

        $user->removeCategory($category);
        $user->addCategory($category);

        $this->assertTrue($user->getCategories()->contains($category));
        $this->assertSame($user, $category->getOwner());
    }

    public function testAddSubCategoryToUser(): void
    {
        $user = UserFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['owner' => $user]);

        $this->assertTrue($user->getSubcategories()->contains($subcategory));
        $this->assertSame($user, $subcategory->getOwner());
    }

    public function testRemoveSubcategoryFromUser(): void
    {
        $user = UserFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['owner' => $user]);

        $user->removeSubcategory($subcategory);

        $this->assertFalse($user->getSubcategories()->contains($subcategory));
        $this->assertNull($subcategory->getOwner());
    }

    public function testReAddSubcategoryToUser(): void
    {
        $user = UserFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['owner' => $user]);

        $user->removeSubcategory($subcategory);
        $user->addSubcategory($subcategory);

        $this->assertTrue($user->getSubcategories()->contains($subcategory));
        $this->assertSame($user, $subcategory->getOwner());
    }

    public function testAddAddressToUser(): void
    {
        $user = UserFactory::createOne();
        $address = AddressFactory::createOne(['customer' => $user]);

        $this->assertTrue($user->getAddresses()->contains($address));
        $this->assertSame($user, $address->getCustomer());
    }

    public function testRemoveAddressFromUser(): void
    {
        $user = UserFactory::createOne();
        $address = AddressFactory::createOne(['customer' => $user]);

        $user->removeAddress($address);

        $this->assertFalse($user->getAddresses()->contains($address));
        $this->assertNull($address->getCustomer());
    }

    public function testReAddAddressToUser(): void
    {
        $user = UserFactory::createOne();
        $address = AddressFactory::createOne(['customer' => $user]);

        $user->removeAddress($address);
        $user->addAddress($address);

        $this->assertTrue($user->getAddresses()->contains($address));
        $this->assertSame($user, $address->getCustomer());
    }

    public function testAddCustomerOrderToUser(): void
    {
        $user = UserFactory::createOne();
        $address = AddressFactory::createOne([
            'customer' => $user,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);
        $customerOrder = CustomerOrderFactory::createOne(['customer' => $user, 'billingAddress' => $address]);

        $this->assertTrue($user->getCustomerOrders()->contains($customerOrder));
        $this->assertSame($user, $customerOrder->getCustomer());
    }

    public function testRemoveCustomerOrderFromUser(): void
    {
        $user = UserFactory::createOne();
        $address = AddressFactory::createOne([
            'customer' => $user,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);
        $customerOrder = CustomerOrderFactory::createOne(['customer' => $user, 'billingAddress' => $address]);

        $user->removeCustomerOrder($customerOrder);

        $this->assertFalse($user->getCustomerOrders()->contains($customerOrder));
    }

    public function testReAddCustomerOrderToUser(): void
    {
        $user = UserFactory::createOne();
        $address = AddressFactory::createOne([
            'customer' => $user,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);
        $customerOrder = CustomerOrderFactory::createOne(['customer' => $user, 'billingAddress' => $address]);

        $user->removeCustomerOrder($customerOrder);
        $user->addCustomerOrder($customerOrder);

        $this->assertTrue($user->getCustomerOrders()->contains($customerOrder));
        $this->assertSame($user, $customerOrder->getCustomer());
    }

    public function testAddStatusChangeLogToUser(): void
    {
        $user = UserFactory::createOne();
        $statusChangeLog = StatusChangeLogFactory::createOne(['user' => $user]);

        $this->assertTrue($user->getStatusChangeLogs()->contains($statusChangeLog));
        $this->assertSame($user, $statusChangeLog->getUser());
    }

    public function testRemoveStatusChangeLogFromUser(): void
    {
        $user = UserFactory::createOne();
        $statusChangeLog = StatusChangeLogFactory::createOne(['user' => $user]);

        $user->removeStatusChangeLog($statusChangeLog);

        $this->assertFalse($user->getStatusChangeLogs()->contains($statusChangeLog));
    }

    public function testReAddStatusChangeLogToUser(): void
    {
        $user = UserFactory::createOne();
        $statusChangeLog = StatusChangeLogFactory::createOne(['user' => $user]);

        $user->removeStatusChangeLog($statusChangeLog);
        $user->addStatusChangeLog($statusChangeLog);

        $this->assertTrue($user->getStatusChangeLogs()->contains($statusChangeLog));
        $this->assertSame($user, $statusChangeLog->getUser());
    }
}
