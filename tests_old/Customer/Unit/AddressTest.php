<?php

namespace App\Tests\Customer\Unit;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $user = $this->createMock(User::class);

        $address = new Address()
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

    public function testAddCustomerOrder(): void
    {
        $address= new Address();
        $customerOrder = $this->createMock(CustomerOrder::class);

        // Test adding a customer order
        $customerOrder->expects($this->once())
            ->method('assignShippingAddress')
            ->with($address);

        $address->addCustomerOrder($customerOrder);
        $this->assertCount(1, $address->getCustomerOrders());
        $this->assertTrue($address->getCustomerOrders()->contains($customerOrder));
    }

    public function testRemoveCustomerOrder(): void
    {
        $address= new Address();
        $customerOrder = $this->createMock(CustomerOrder::class);

        // Add the customer order first to set up the state
        $address->addCustomerOrder($customerOrder);

        // Test removing a customer order
        $customerOrder->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($address);

        $address->removeCustomerOrder($customerOrder);
        $this->assertCount(0, $address->getCustomerOrders());
    }

    public function testAddPurchaseOrder(): void
    {
        $address= new Address();
        $purchaseOrder = $this->createMock(PurchaseOrder::class);

        // Test adding a customer order
        $purchaseOrder->expects($this->once())
            ->method('setShippingAddress')
            ->with($address);

        $address->addPurchaseOrder($purchaseOrder);
        $this->assertCount(1, $address->getPurchaseOrders());
        $this->assertTrue($address->getPurchaseOrders()->contains($purchaseOrder));
    }

    public function testRemovePurchaseOrder(): void
    {
        $address= new Address();
        $purchaseOrder = $this->createMock(PurchaseOrder::class);

        // Add the purchase order first to set up the state
        $address->addPurchaseOrder($purchaseOrder);

        // Test removing a purchase order
        $purchaseOrder->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($address);

        $address->removePurchaseOrder($purchaseOrder);
        $this->assertCount(0, $address->getPurchaseOrders());
    }
}
