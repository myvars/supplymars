<?php

namespace App\Tests\Reporting\Application\Handler;

use App\Reporting\Application\Handler\CalculateOrderSalesHandler;
use App\Reporting\Domain\Model\SalesType\OrderSales;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\OrderSalesFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CalculateOrderSalesHandlerTest extends KernelTestCase
{
    use Factories;

    private CalculateOrderSalesHandler $handler;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CalculateOrderSalesHandler::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProcessCreatesOrderSalesForDate(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process($date);

        $orderSales = $this->em->getRepository(OrderSales::class)->findAll();
        self::assertCount(1, $orderSales);
        self::assertSame($date, $orderSales[0]->getDateString());
    }

    public function testProcessWithNoOrdersCreatesNoRecords(): void
    {
        $date = '2000-01-01';

        $this->handler->process($date);

        $orderSales = $this->em->getRepository(OrderSales::class)->findAll();
        self::assertCount(0, $orderSales);
    }

    public function testProcessDeletesExistingRecordsBeforeCreating(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        OrderSalesFactory::createOne([
            'dateString' => $date,
            'orderCount' => 5,
            'orderValue' => '100.00',
        ]);

        $this->em->clear();

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process($date);

        $orderSales = $this->em->getRepository(OrderSales::class)->findAll();
        self::assertCount(1, $orderSales);
        self::assertSame(1, $orderSales[0]->getOrderCount());
    }

    public function testProcessCalculatesCorrectMetrics(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'quantity' => 2]);

        $this->handler->process($date);

        $orderSales = $this->em->getRepository(OrderSales::class)->findAll();
        self::assertCount(1, $orderSales);

        $sales = $orderSales[0];
        self::assertSame(1, $sales->getOrderCount());
        self::assertNotEmpty($sales->getOrderValue());
        self::assertNotEmpty($sales->getAverageOrderValue());
    }

    public function testProcessWithMultipleOrdersAggregatesCorrectly(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        $order1 = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order1]);

        $order2 = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order2]);

        $order3 = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order3]);

        $this->handler->process($date);

        $orderSales = $this->em->getRepository(OrderSales::class)->findAll();
        self::assertCount(1, $orderSales);
        self::assertSame(3, $orderSales[0]->getOrderCount());
    }
}
