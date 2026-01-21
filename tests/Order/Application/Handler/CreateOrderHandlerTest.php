<?php

namespace App\Tests\Order\Application\Handler;

use App\Order\Application\Command\CreateOrder;
use App\Order\Application\Handler\CreateOrderHandler;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Domain\ValueObject\ShippingMethod;
use App\Tests\Shared\Factory\AddressFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CreateOrderHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateOrderHandler $handler;

    private OrderRepository $orders;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateOrderHandler::class);
        $this->orders = self::getContainer()->get(OrderRepository::class);
    }

    public function testHandleCreatesOrder(): void
    {
        $customer = UserFactory::createOne();
        AddressFactory::createOne([
            'customer' => $customer,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);
        VatRateFactory::new()->withStandardRate()->create();

        $command = new CreateOrder(
            customerId: $customer->getId(),
            shippingMethod: ShippingMethod::THREE_DAY,
            customerOrderRef: 'TEST-REF-001',
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        $orderId = $result->payload;
        $persisted = $this->orders->get($orderId);
        self::assertNotNull($persisted);
        self::assertSame($customer->getId(), $persisted->getCustomer()->getId());
        self::assertSame(ShippingMethod::THREE_DAY, $persisted->getShippingMethod());
        self::assertSame('TEST-REF-001', $persisted->getCustomerOrderRef());
    }

    public function testHandleFailsWhenCustomerNotFound(): void
    {
        VatRateFactory::new()->withStandardRate()->create();

        $command = new CreateOrder(
            customerId: 999999,
            shippingMethod: ShippingMethod::THREE_DAY,
            customerOrderRef: null,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Customer not found', $result->message);
    }

    public function testHandleFailsWhenDefaultVatRateNotFound(): void
    {
        $customer = UserFactory::createOne();
        AddressFactory::createOne([
            'customer' => $customer,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);

        $command = new CreateOrder(
            customerId: $customer->getId(),
            shippingMethod: ShippingMethod::NEXT_DAY,
            customerOrderRef: null,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Default VAT rate not found', $result->message);
    }
}
