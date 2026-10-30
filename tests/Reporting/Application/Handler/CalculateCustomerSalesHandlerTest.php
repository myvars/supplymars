<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\Handler;

use App\Reporting\Application\Handler\CalculateCustomerSalesHandler;
use App\Tests\Shared\Factory\CustomerActivitySalesFactory;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\CustomerSalesFactory;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CalculateCustomerSalesHandlerTest extends KernelTestCase
{
    private CalculateCustomerSalesHandler $handler;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CalculateCustomerSalesHandler::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProcessCreatesCustomerSalesForDate(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process($date);

        CustomerSalesFactory::assert()->count(1);
        $customerSales = CustomerSalesFactory::all();
        self::assertSame($date, $customerSales[0]->getDateString());
    }

    public function testOrderValueIsExVatLikeTheOrderReports(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);
        self::assertNotSame($order->getTotalPrice(), $order->getTotalPriceIncVat());

        $this->handler->process(new \DateTime()->format('Y-m-d'));

        CustomerSalesFactory::assert()->count(1);
        $customerSales = CustomerSalesFactory::all();
        self::assertSame((float) $order->getTotalPrice(), (float) $customerSales[0]->getOrderValue());
    }

    public function testProcessWithNoOrdersCreatesNoCustomerSalesRecords(): void
    {
        $date = '2000-01-01';

        $this->handler->process($date);

        CustomerSalesFactory::assert()->empty();
    }

    public function testProcessCreatesCustomerActivityForDate(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        UserFactory::createOne();
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process($date);

        CustomerActivitySalesFactory::assert()->count(1);
        $activity = CustomerActivitySalesFactory::all();
        self::assertSame($date, $activity[0]->getDateString());
        self::assertGreaterThanOrEqual(0, $activity[0]->getTotalCustomers());
    }

    public function testProcessDeletesExistingRecordsBeforeCreating(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        CustomerSalesFactory::createOne([
            'customerId' => 999,
            'dateString' => $date,
            'orderCount' => 5,
            'orderValue' => '100.00',
            'itemCount' => 10,
        ]);

        $this->em->clear();

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process($date);

        CustomerSalesFactory::assert()->count(1);
        $customerSales = CustomerSalesFactory::all();
        self::assertSame(1, $customerSales[0]->getOrderCount());
    }

    public function testProcessWithMultipleCustomerOrdersCreatesMultipleRecords(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        $order1 = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order1]);

        $order2 = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order2]);

        $this->handler->process($date);

        CustomerSalesFactory::assert()->countGreaterThanOrEqual(1);
    }

    public function testProcessWithNoOrdersStillCreatesActivityRecord(): void
    {
        $date = '2000-01-01';

        UserFactory::createOne();

        $this->handler->process($date);

        CustomerActivitySalesFactory::assert()->count(1);
        $activity = CustomerActivitySalesFactory::all();
        self::assertSame(0, $activity[0]->getActiveCustomers());
        self::assertSame(0, $activity[0]->getNewCustomers());
        self::assertSame(0, $activity[0]->getReturningCustomers());
    }
}
