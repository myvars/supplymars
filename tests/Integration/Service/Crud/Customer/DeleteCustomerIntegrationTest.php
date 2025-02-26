<?php

namespace App\Tests\Integration\Service\Crud\Customer;

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
        $customer = UserFactory::createOne()->_real();
        $customerId = $customer->getId();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customer);

        $this->deleteCustomer->handle($crudOptions);

        $this->assertNull(UserFactory::Repository()->find($customerId));
    }
}