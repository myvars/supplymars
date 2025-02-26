<?php

namespace App\Tests\Integration\Service\Order;

use App\Enum\OrderStatus;
use App\Factory\CustomerOrderItemFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\CancelOrderItem;
use App\Service\Utility\DomainEventDispatcher;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CancelOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private CancelOrderItem $cancelOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $domainEventDispatcher = static::getContainer()->get(DomainEventDispatcher::class);
        $this->cancelOrderItem = new CancelOrderItem($entityManager, $domainEventDispatcher);
        StaffUserStory::load();
    }

    public function testHandleWithValidOrderItem(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne()->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customerOrderItem);

        $this->cancelOrderItem->handle($crudOptions);

        $this->assertSame(OrderStatus::CANCELLED, $customerOrderItem->getStatus());
    }
}