<?php

namespace App\Tests\New\Integration\Entity;

use App\Factory\AddressFactory;
use App\Factory\CategoryFactory;
use App\Factory\CustomerOrderFactory;
use App\Factory\StatusChangeLogFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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
            'email' => 'valid@example.com',
            'fullName' => 'Valid User',
            'password' => 'validpassword',
        ])->_real();

        $errors = $this->validator->validate($user);
        $this->assertCount(0, $errors);
    }

    public function testInvalidEmail(): void
    {
        $user = UserFactory::createOne(['email' => 'invalid-email'])->_real();

        $errors = $this->validator->validate($user);
        $this->assertSame('This value is not a valid email address.', $errors[0]->getMessage());
    }

    public function testBlankFullName(): void
    {
        $user = UserFactory::createOne(['fullName' => ''])->_real();

        $errors = $this->validator->validate($user);
        $this->assertSame('Please enter a full name', $errors[0]->getMessage());
    }

    public function testUserPersistence(): void
    {
        $user = UserFactory::createOne([
            'email' => 'test@example.com',
            'roles' => [],
            'fullName' => 'John Doe',
            'password' => 'password123',
            'isVerified' => true,
            'isStaff' => false,
        ]);

        $persistedUser = UserFactory::repository()->find($user->getId());
        $this->assertEquals('test@example.com', $persistedUser->getEmail());
    }

    public function testAddCategoryToUser()
    {
        $user = UserFactory::createOne()->_real();
        $category = CategoryFactory::createOne(['owner' => $user])->_real();

        $this->assertTrue($user->getCategories()->contains($category));
        $this->assertSame($user, $category->getOwner());
    }

    public function testRemoveCategoryFromUser()
    {
        $user = UserFactory::createOne()->_real();
        $category = CategoryFactory::createOne(['owner' => $user])->_real();

        $user->removeCategory($category);

        $this->assertFalse($user->getCategories()->contains($category));
        $this->assertNull($category->getOwner());
    }

    public function testReAddCategoryToUser()
    {
        $user = UserFactory::createOne()->_real();
        $category = CategoryFactory::createOne(['owner' => $user])->_real();

        $user->removeCategory($category);
        $user->addCategory($category);

        $this->assertTrue($user->getCategories()->contains($category));
        $this->assertSame($user, $category->getOwner());
    }

    public function testAddSubCategoryToUser()
    {
        $user = UserFactory::createOne()->_real();
        $subcategory = SubcategoryFactory::createOne(['owner' => $user])->_real();

        $this->assertTrue($user->getSubcategories()->contains($subcategory));
        $this->assertSame($user, $subcategory->getOwner());
    }

    public function testRemoveSubcategoryFromUser()
    {
        $user = UserFactory::createOne()->_real();
        $subcategory = SubcategoryFactory::createOne(['owner' => $user])->_real();

        $user->removeSubcategory($subcategory);

        $this->assertFalse($user->getSubcategories()->contains($subcategory));
        $this->assertNull($subcategory->getOwner());
    }

    public function testReAddSubcategoryToUser()
    {
        $user = UserFactory::createOne()->_real();
        $subcategory = SubcategoryFactory::createOne(['owner' => $user])->_real();

        $user->removeSubcategory($subcategory);
        $user->addSubcategory($subcategory);

        $this->assertTrue($user->getSubcategories()->contains($subcategory));
        $this->assertSame($user, $subcategory->getOwner());
    }

    public function testAddAddressToUser()
    {
        $user = UserFactory::createOne()->_real();
        $address = AddressFactory::createOne(['customer' => $user])->_real();

        $this->assertTrue($user->getAddresses()->contains($address));
        $this->assertSame($user, $address->getCustomer());
    }

    public function testRemoveAddressFromUser()
    {
        $user = UserFactory::createOne()->_real();
        $address = AddressFactory::createOne(['customer' => $user])->_real();

        $user->removeAddress($address);

        $this->assertFalse($user->getAddresses()->contains($address));
        $this->assertNull($address->getCustomer());
    }

    public function testReAddAddressToUser()
    {
        $user = UserFactory::createOne()->_real();
        $address = AddressFactory::createOne(['customer' => $user])->_real();

        $user->removeAddress($address);
        $user->addAddress($address);

        $this->assertTrue($user->getAddresses()->contains($address));
        $this->assertSame($user, $address->getCustomer());
    }

    public function testAddCustomerOrderToUser()
    {
        $user = UserFactory::createOne()->_real();
        $customerOrder = CustomerOrderFactory::createOne(['customer' => $user])->_real();

        $this->assertTrue($user->getCustomerOrders()->contains($customerOrder));
        $this->assertSame($user, $customerOrder->getCustomer());
    }

    public function testRemoveCustomerOrderFromUser()
    {
        $user = UserFactory::createOne()->_real();
        $customerOrder = CustomerOrderFactory::createOne(['customer' => $user])->_real();

        $user->removeCustomerOrder($customerOrder);

        $this->assertFalse($user->getCustomerOrders()->contains($customerOrder));
    }

    public function testReAddCustomerOrderToUser()
    {
        $user = UserFactory::createOne()->_real();
        $customerOrder = CustomerOrderFactory::createOne(['customer' => $user])->_real();

        $user->removeCustomerOrder($customerOrder);
        $user->addCustomerOrder($customerOrder);

        $this->assertTrue($user->getCustomerOrders()->contains($customerOrder));
        $this->assertSame($user, $customerOrder->getCustomer());
    }

    public function testAddStatusChangeLogToUser()
    {
        $user = UserFactory::createOne()->_real();
        $statusChangeLog = StatusChangeLogFactory::createOne(['user' => $user])->_real();

        $this->assertTrue($user->getStatusChangeLogs()->contains($statusChangeLog));
        $this->assertSame($user, $statusChangeLog->getUser());
    }

    public function testRemoveStatusChangeLogFromUser()
    {
        $user = UserFactory::createOne()->_real();
        $statusChangeLog = StatusChangeLogFactory::createOne(['user' => $user])->_real();

        $user->removeStatusChangeLog($statusChangeLog);

        $this->assertFalse($user->getStatusChangeLogs()->contains($statusChangeLog));
    }

    public function testReAddStatusChangeLogToUser()
    {
        $user = UserFactory::createOne()->_real();
        $statusChangeLog = StatusChangeLogFactory::createOne(['user' => $user])->_real();

        $user->removeStatusChangeLog($statusChangeLog);
        $user->addStatusChangeLog($statusChangeLog);

        $this->assertTrue($user->getStatusChangeLogs()->contains($statusChangeLog));
        $this->assertSame($user, $statusChangeLog->getUser());
    }
}