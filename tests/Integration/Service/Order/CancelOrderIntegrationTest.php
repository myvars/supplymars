<?php

namespace App\Tests\Integration\Service\Order;

use App\Enum\OrderStatus;
use App\Factory\CustomerOrderItemFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\CancelOrder;
use App\Service\Utility\DomainEventDispatcher;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CancelOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private CancelOrder $cancelOrder;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $domainEventDispatcher = static::getContainer()->get(DomainEventDispatcher::class);
        $this->cancelOrder = new CancelOrder($entityManager, $domainEventDispatcher);
        StaffUserStory::load();
    }

    public function testHandleWithValidOrder(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne()->_real();
        $customerOrder = $customerOrderItem->getCustomerOrder();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customerOrder);

        $this->cancelOrder->handle($crudOptions);

        $this->assertSame(OrderStatus::CANCELLED, $customerOrder->getStatus());
    }
}