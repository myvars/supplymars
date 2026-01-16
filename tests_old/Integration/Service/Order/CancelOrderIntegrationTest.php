<?php

namespace App\Tests\Integration\Service\Order;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\CancelOrder;
use Doctrine\ORM\EntityManagerInterface;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\CustomerOrderItemFactory;
use Zenstruck\Foundry\Test\Factories;

class CancelOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private CancelOrder $cancelOrder;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->cancelOrder = new CancelOrder($em);
        StaffUserStory::load();
    }

    public function testHandleWithValidOrder(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne();
        $customerOrder = $customerOrderItem->getCustomerOrder();

        $context = new CrudContext();
        $context->setEntity($customerOrder);

        ($this->cancelOrder)($context);

        $this->assertSame(OrderStatus::CANCELLED, $customerOrder->getStatus());
    }
}
