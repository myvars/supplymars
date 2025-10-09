<?php

namespace App\Tests\Integration\Service\Customer;

use App\Factory\AddressFactory;
use App\Factory\CustomerOrderFactory;
use App\Factory\UserFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Customer\DeleteCustomer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class DeleteCustomerIntegrationTest extends KernelTestCase
{
    use Factories;

    private DeleteCustomer $deleteCustomer;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->deleteCustomer = new DeleteCustomer($entityManager);
    }

    public function testHandleWithValidCustomer(): void
    {
        $customer = UserFactory::createOne();
        $address = AddressFactory::createOne(['customer' => $customer]);
        $customer->addAddress($address);
        $customerId = $customer->getId();
        $addressId = $address->getId();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customer);

        $this->deleteCustomer->handle($crudOptions);

        $this->assertNull(UserFactory::repository()->find($customerId));
        $this->assertNull(AddressFactory::repository()->find($addressId));
    }

    public function testHandleWithCustomerOrders(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer has order history and cannot be deleted');

        $customer = UserFactory::createOne();
        CustomerOrderFactory::createOne(['customer' => $customer]);

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customer);

        $this->deleteCustomer->handle($crudOptions);
    }
}