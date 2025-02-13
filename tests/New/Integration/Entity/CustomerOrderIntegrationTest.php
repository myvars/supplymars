<?php

namespace App\Tests\New\Integration\Entity;

use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\ShippingMethod;
use App\Factory\AddressFactory;
use App\Factory\CustomerOrderFactory;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CustomerOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidCustomerOrder(): void
    {
        $customer = UserFactory::new()->create();
        $billingAddress = AddressFactory::createOne([
            'customer' => $customer,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);
        $shippingMethod = ShippingMethod::NEXT_DAY;
        $vatRate = VatRateFactory::new()->standard();
        $customerOrderRef = 'TEST-001';

        $order = CustomerOrderFactory::createOne([
            'customer' => $customer,
            'shippingMethod' => $shippingMethod,
            'vatRate' => $vatRate,
            'customerOrderRef' => $customerOrderRef
        ]);

        $errors = $this->validator->validate($order);
        $this->assertCount(0, $errors);
    }

    public function testCustomerOrderPersistence(): void
    {
        $order = CustomerOrderFactory::createOne();

        $persistedOrder = CustomerOrderFactory::repository()->find($order->getId());
        $this->assertEquals($order->getId(), $persistedOrder->getId());
    }

    public function testAddCustomerOrderItemToOrder(): void
    {
        $order = CustomerOrderFactory::createOne()->_real();
        $item = CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $order->addCustomerOrderItem($item);

        $this->assertTrue($order->getCustomerOrderItems()->contains($item));
        $this->assertSame($order, $item->getCustomerOrder());
    }

    public function testRemoveCustomerOrderItemFromOrder(): void
    {
        $order = CustomerOrderFactory::createOne()->_real();
        $item = CustomerOrderItemFactory::createOne(['customerOrder' => $order])->_real();

        $order->addCustomerOrderItem($item);
        $order->removeCustomerOrderItem($item);

        $this->assertFalse($order->getCustomerOrderItems()->contains($item));
    }

    public function testReAddCustomerOrderItemToOrder(): void
    {
        $order = CustomerOrderFactory::createOne()->_real();
        $item = CustomerOrderItemFactory::createOne(['customerOrder' => $order])->_real();

        $order->removeCustomerOrderItem($item);
        $order->addCustomerOrderItem($item);

        $this->assertTrue($order->getCustomerOrderItems()->contains($item));
        $this->assertSame($order, $item->getCustomerOrder());
    }

    public function testSetOrderLock(): void
    {
        $order = CustomerOrderFactory::createOne()->_real();
        $order->lockOrder(UserFactory::createOne());

        $this->assertInstanceOf(User::class, $order->getOrderLock());
    }

    public function testCancelOrder(): void
    {
        $order = CustomerOrderFactory::createOne()->_real();
        $order->cancelOrder();

        $this->assertSame(OrderStatus::CANCELLED, $order->getStatus());
    }

    public function testGenerateStatus(): void
    {
        $order = CustomerOrderFactory::createOne()->_real();
        $order->generateStatus();

        $this->assertSame(OrderStatus::getDefault(), $order->getStatus());
    }

    public function testGetLineCount(): void
    {
        $order = CustomerOrderFactory::createOne()->_real();
        CustomerOrderItemFactory::createMany(2, ['customerOrder' => $order]);

        $this->assertSame(2, $order->getLineCount());
    }

    public function testGetItemCount(): void
    {
        $order = CustomerOrderFactory::createOne()->_real();
        CustomerOrderItemFactory::createMany(2, ['customerOrder' => $order, 'quantity' => 2]);

        $this->assertSame(4, $order->getItemCount());
    }
}