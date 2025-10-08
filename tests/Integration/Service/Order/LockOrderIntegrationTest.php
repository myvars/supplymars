<?php

namespace App\Tests\Integration\Service\Order;

use App\Entity\CustomerOrder;
use App\Factory\CustomerOrderFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\LockOrder;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Zenstruck\Foundry\Test\Factories;

class LockOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private LockOrder $lockOrder;

    private Security $security;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->lockOrder = new LockOrder($entityManager, $this->security);
        StaffUserStory::load();
    }

    public function testHandleWithValidCustomerOrder(): void
    {
        $customerOrder = CustomerOrderFactory::createOne()->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customerOrder);

        $this->lockOrder->handle($crudOptions);

        $updatedCustomerOrder = CustomerOrderFactory::repository()->find($customerOrder->getId());

        $this->assertInstanceOf(CustomerOrder::class, $updatedCustomerOrder);
        $this->assertSame($this->security->getUser(), $updatedCustomerOrder->getOrderLock());
    }
}