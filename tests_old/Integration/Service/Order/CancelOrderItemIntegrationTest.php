<?php

namespace App\Tests\Integration\Service\Order;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\CancelOrderItem;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\CustomerOrderItemFactory;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CancelOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private CancelOrderItem $cancelOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->cancelOrderItem = new CancelOrderItem($em);
        StaffUserStory::load();
    }

    public function testHandleWithValidOrderItem(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne();

        $context = new CrudContext();
        $context->setEntity($customerOrderItem);

        ($this->cancelOrderItem)($context);

        $this->assertSame(OrderStatus::CANCELLED, $customerOrderItem->getStatus());
    }
}
