<?php

namespace App\Tests\Integration\Service\Order;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\LockOrder;
use Doctrine\ORM\EntityManagerInterface;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use tests\Shared\Factory\CustomerOrderFactory;
use Zenstruck\Foundry\Test\Factories;

class LockOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private LockOrder $lockOrder;

    private Security $security;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->lockOrder = new LockOrder($em, $this->security);
        StaffUserStory::load();
    }

    public function testHandleWithValidCustomerOrder(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();

        $context = new CrudContext();
        $context->setEntity($customerOrder);

        ($this->lockOrder)($context);

        $updatedCustomerOrder = CustomerOrderFactory::repository()->find($customerOrder->getId());

        $this->assertInstanceOf(CustomerOrder::class, $updatedCustomerOrder);
        $this->assertSame($this->security->getUser(), $updatedCustomerOrder->getOrderLock());
    }
}
