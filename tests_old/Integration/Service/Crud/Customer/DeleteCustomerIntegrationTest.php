<?php

namespace App\Tests\Integration\Service\Crud\Customer;

use App\Customer\Application\Handler\DeleteCustomerHandler;
use App\Customer\Domain\Repository\UserRepository;
use App\Service\Crud\Common\CrudContext;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class DeleteCustomerIntegrationTest extends KernelTestCase
{
    use Factories;

    private DeleteCustomerHandler $deleteCustomer;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $customers = static::getContainer()->get(UserRepository::class);
        $this->deleteCustomer = new DeleteCustomerHandler($customers, $em);
    }

    public function testHandleWithValidCustomer(): void
    {
        $customer = UserFactory::createOne();
        $customerId = $customer->getId();

        $context = new CrudContext();
        $context->setEntity($customer);

        ($this->deleteCustomer)($context);

        $this->assertNull(UserFactory::Repository()->find($customerId));
    }
}
