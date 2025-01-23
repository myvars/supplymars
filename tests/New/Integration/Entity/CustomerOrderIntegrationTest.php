<?php

namespace App\Tests\New\Integration\Entity;

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
        $shippingAddress = AddressFactory::createOne();
        $billingAddress = AddressFactory::createOne();
        $vatRate = VatRateFactory::createOne();

        $order = CustomerOrderFactory::createOne([
            'customer' => $customer,
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'shippingMethod' => ShippingMethod::NEXT_DAY,
            'dueDate' => new \DateTimeImmutable(),
            'shippingPrice' => '10.00',
            'shippingPriceIncVat' => '12.00',
            'status' => OrderStatus::getDefault(),
        ]);

        $errors = $this->validator->validate($order);
        $this->assertCount(0, $errors);
    }

//    public function testCustomerIsRequired(): void
//    {
//        $order = CustomerOrderFactory::new()->withoutPersisting()->create(['customer' => null]);
//
//        $violations = $this->validator->validate($order);
//        $this->assertSame('Please enter a customer', $violations[0]->getMessage());
//    }
//
//    public function testShippingAddressIsRequired(): void
//    {
//        $order = CustomerOrderFactory::new()->withoutPersisting()->create(['shippingAddress' => null]);
//
//        $violations = $this->validator->validate($order);
//        $this->assertSame('Please enter a shipping address', $violations[0]->getMessage());
//    }
//
//    public function testBillingAddressIsRequired(): void
//    {
//        $order = CustomerOrderFactory::new()->withoutPersisting()->create(['billingAddress' => null]);
//
//        $violations = $this->validator->validate($order);
//        $this->assertSame('Please enter a billing address', $violations[0]->getMessage());
//    }
//
//    public function testShippingMethodIsRequired(): void
//    {
//        $order = CustomerOrderFactory::new()->withoutPersisting()->create(['shippingMethod' => null]);
//
//        $violations = $this->validator->validate($order);
//        $this->assertSame('Please enter a shipping method', $violations[0]->getMessage());
//    }
//
//    public function testDueDateIsRequired(): void
//    {
//        $order = CustomerOrderFactory::new()->withoutPersisting()->create(['dueDate' => null]);
//
//        $violations = $this->validator->validate($order);
//        $this->assertSame('Please enter a due date', $violations[0]->getMessage());
//    }
//
//    public function testShippingPriceIsRequired(): void
//    {
//        $order = CustomerOrderFactory::new()->withoutPersisting()->create(['shippingPrice' => -1]);
//
//        $violations = $this->validator->validate($order);
//        $this->assertSame('Please enter a shipping price', $violations[0]->getMessage());
//    }
//
//    public function testShippingPriceIncVatIsRequired(): void
//    {
//        $order = CustomerOrderFactory::new()->withoutPersisting()->create(['shippingPriceIncVat' => '']);
//
//        $violations = $this->validator->validate($order);
//        $this->assertSame('Please enter a shipping price inc VAT', $violations[0]->getMessage());
//    }
//
//    public function testStatusIsRequired(): void
//    {
//        $order = CustomerOrderFactory::new()->withoutPersisting()->create(['status' => null]);
//
//        $violations = $this->validator->validate($order);
//        $billingAddress = AddressFactory::createOne();
//        $vatRate = VatRateFactory::createOne();
//
//        $order = CustomerOrderFactory::createOne(['status' => '']);
//
//        $this->assertSame('Please enter a status', $violations[0]->getMessage());
//    }

    public function testCustomerOrderPersistence(): void
    {
        $customer = UserFactory::new()->create();
        $shippingAddress = AddressFactory::createOne();
        $billingAddress = AddressFactory::createOne();
        $vatRate = VatRateFactory::createOne();

        $order = CustomerOrderFactory::createOne([
            'customer' => $customer,
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'shippingMethod' => ShippingMethod::NEXT_DAY,
            'dueDate' => new \DateTimeImmutable(),
            'shippingPrice' => '10.00',
            'shippingPriceIncVat' => '12.00',
            'status' => OrderStatus::getDefault(),
        ]);

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
        $this->assertNull($item->getCustomerOrder());
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
}