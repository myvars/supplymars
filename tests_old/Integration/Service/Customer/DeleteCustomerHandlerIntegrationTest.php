<?php

namespace App\Tests\Integration\Service\Customer;

use App\Customer\Application\Handler\DeleteCustomerHandler;
use App\Service\Crud\Common\CrudContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\AddressFactory;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;

class DeleteCustomerHandlerIntegrationTest extends KernelTestCase
{
    use Factories;

    private DeleteCustomerHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = static::getContainer()->get(DeleteCustomerHandler::class);
    }

    public function testHandleWithValidCustomer(): void
    {
        $customer = UserFactory::createOne();
        $address = AddressFactory::createOne(['customer' => $customer]);
        $customer->addAddress($address);
        $customerId = $customer->getId();
        $addressId = $address->getId();

        $context = new CrudContext();
        $context->setEntity($customer);

        ($this->handler)($context);

        $this->assertNull(UserFactory::repository()->find($customerId));
        $this->assertNull(AddressFactory::repository()->find($addressId));
    }

    public function testHandleWithCustomerOrders(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer cannot be deleted');

        $customer = UserFactory::createOne();
        CustomerOrderFactory::createOne(['customer' => $customer]);

        $context = new CrudContext();
        $context->setEntity($customer);

        ($this->handler)($context);
    }
}
