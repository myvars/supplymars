<?php

namespace App\Tests\Customer\Unit;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $user = new User();
        $user->setEmail('test@example.com')
            ->setRoles(['TEST_USER'])
            ->setFullName('John Doe')
            ->setPassword('password123')
            ->setIsVerified(true)
            ->setIsStaff(false);

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertContains('TEST_USER', $user->getRoles());
        $this->assertEquals('John Doe', $user->getFullName());
        $this->assertEquals('password123', $user->getPassword());
        $this->assertTrue($user->isVerified());
        $this->assertFalse($user->isStaff());
    }

    public function testUserIdentifierisEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testToString(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertEquals('test@example.com', (string) $user);
    }

    public function testSetIsStaff(): void
    {
        $user = new User();
        $user->setIsStaff(true);

        $this->assertTrue($user->isStaff());
        $this->assertTrue($user->isAdmin());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());

        $user->setIsStaff(false);

        $this->assertFalse($user->isStaff());
        $this->assertFalse($user->isAdmin());
        $this->assertNotContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testAddCategory(): void
    {
        $user = new User();
        $category = $this->createMock(Category::class);

        // Test adding a category
        $category->expects($this->once())
            ->method('assignOwner')
            ->with($user);

        $user->addCategory($category);
        $this->assertCount(1, $user->getCategories());
        $this->assertTrue($user->getCategories()->contains($category));
    }

    public function testRemoveCategory(): void
    {
        $user = new User();
        $category = $this->createMock(Category::class);

        // Add the category first to set up the state
        $user->addCategory($category);

        // Test removing a category
        $category->expects($this->once())
            ->method('getOwner')
            ->willReturn($user);

        $category->expects($this->once())
            ->method('assignOwner')
            ->with(null);

        $user->removeCategory($category);
        $this->assertCount(0, $user->getCategories());
        $this->assertFalse($user->getCategories()->contains($category));
    }

    public function testAddSubcategory(): void
    {
        $user = new User();
        $subcategory = $this->createMock(Subcategory::class);

        // Test adding a subcategory
        $subcategory->expects($this->once())
            ->method('assignOwner')
            ->with($user);

        $user->addSubcategory($subcategory);
        $this->assertCount(1, $user->getSubcategories());
        $this->assertTrue($user->getSubcategories()->contains($subcategory));
    }

    public function testRemoveSubcategory(): void
    {
        $user = new User();
        $subcategory = $this->createMock(Subcategory::class);

        // Add the subcategory first to set up the state
        $user->addSubcategory($subcategory);

        // Test removing a subcategory
        $subcategory->expects($this->once())
            ->method('getOwner')
            ->willReturn($user);

        $subcategory->expects($this->once())
            ->method('assignOwner')
            ->with(null);

        $user->removeSubcategory($subcategory);
        $this->assertCount(0, $user->getSubcategories());
    }

    public function testAddAddress(): void
    {
        $user = new User();
        $address = $this->createMock(Address::class);

        // Test adding an address
        $address->expects($this->once())
            ->method('assignCustomer')
            ->with($user);

        $user->addAddress($address);
        $this->assertCount(1, $user->getAddresses());
        $this->assertTrue($user->getAddresses()->contains($address));
    }

    public function testRemoveAddress(): void
    {
        $user = new User();
        $address = $this->createMock(Address::class);

        // Add the category first to set up the state
        $user->addAddress($address);

        // Test removing a category
        $address->expects($this->once())
            ->method('getCustomer')
            ->willReturn($user);

        $address->expects($this->once())
            ->method('assignCustomer')
            ->with(null);

        $user->removeAddress($address);
        $this->assertCount(0, $user->getAddresses());
    }

    public function testAddCustomerOrder(): void
    {
        $user = new User();
        $customerOrder = $this->createMock(CustomerOrder::class);

        // Test adding a customer order
        $customerOrder->expects($this->once())
            ->method('setCustomer')
            ->with($user);

        $user->addCustomerOrder($customerOrder);
        $this->assertCount(1, $user->getCustomerOrders());
        $this->assertTrue($user->getCustomerOrders()->contains($customerOrder));
    }

    public function testRemoveCustomerOrder(): void
    {
        $user = new User();
        $customerOrder = $this->createMock(CustomerOrder::class);

        // Add the customer order first to set up the state
        $user->addCustomerOrder($customerOrder);

        // Test removing a customer order
        $customerOrder->expects($this->once())
            ->method('getCustomer')
            ->willReturn($user);

        $user->removeCustomerOrder($customerOrder);
    }

    public function testAddStatusChangeLog(): void
    {
        $user = new User();
        $statusChangeLog = $this->createMock(StatusChangeLog::class);

        // Test adding a status change log
        $statusChangeLog->expects($this->once())
            ->method('setUser')
            ->with($user);

        $user->addStatusChangeLog($statusChangeLog);
        $this->assertCount(1, $user->getStatusChangeLogs());
        $this->assertTrue($user->getStatusChangeLogs()->contains($statusChangeLog));
    }

    public function testRemoveStatusChangeLog(): void
    {
        $user = new User();
        $statusChangeLog = $this->createMock(StatusChangeLog::class);

        // Add the status change log first to set up the state
        $user->addStatusChangeLog($statusChangeLog);

        // Test removing a status change log
        $statusChangeLog->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $user->removeStatusChangeLog($statusChangeLog);
    }
}
