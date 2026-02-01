<?php

namespace App\Tests\Reporting\Application\Handler;

use App\Reporting\Application\Handler\CalculateCustomerSalesSummaryHandler;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\CustomerGeographicSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesSummary;
use App\Reporting\Domain\Model\SalesType\CustomerSalesType;
use App\Reporting\Domain\Model\SalesType\CustomerSegmentSummary;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\CustomerSalesSummaryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CalculateCustomerSalesSummaryHandlerTest extends KernelTestCase
{
    use Factories;

    private CalculateCustomerSalesSummaryHandler $handler;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CalculateCustomerSalesSummaryHandler::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProcessCreatesAllDurationSummaries(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process();

        $summaries = $this->em->getRepository(CustomerSalesSummary::class)->findAll();
        $durations = array_map(fn (CustomerSalesSummary $s): SalesDuration => $s->getDuration(), $summaries);

        foreach (SalesDuration::cases() as $duration) {
            self::assertContains($duration, $durations, 'Missing duration: ' . $duration->value);
        }
    }

    public function testProcessWithRebuildReplacesExistingData(): void
    {
        CustomerSalesSummaryFactory::createOne([
            'customerSalesType' => CustomerSalesType::create(SalesDuration::LAST_30),
            'dateString' => SalesDuration::LAST_30->getStartDate(),
            'totalCustomers' => 999,
            'activeCustomers' => 888,
        ]);

        $this->em->clear();

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process(rebuild: true);

        $summaries = $this->em->getRepository(CustomerSalesSummary::class)->findBy([
            'duration' => SalesDuration::LAST_30,
        ]);

        self::assertCount(1, $summaries);
        self::assertNotSame(888, $summaries[0]->getActiveCustomers());
    }

    public function testProcessWithNoOrdersCreatesZeroActivitySummaries(): void
    {
        UserFactory::createOne();

        $this->handler->process();

        $summaries = $this->em->getRepository(CustomerSalesSummary::class)->findBy([
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertSame(0, $summaries[0]->getActiveCustomers());
        self::assertSame(0, $summaries[0]->getNewCustomers());
    }

    public function testProcessCreatesGeographicSummaries(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process();

        $geoSummaries = $this->em->getRepository(CustomerGeographicSummary::class)->findBy([
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertGreaterThanOrEqual(1, count($geoSummaries));
    }

    public function testProcessCreatesSegmentSummaries(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process();

        $segmentSummaries = $this->em->getRepository(CustomerSegmentSummary::class)->findBy([
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertGreaterThanOrEqual(1, count($segmentSummaries));
    }

    public function testProcessCalculatesCorrectTotalCustomers(): void
    {
        UserFactory::createMany(3);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process();

        $summaries = $this->em->getRepository(CustomerSalesSummary::class)->findBy([
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertGreaterThanOrEqual(4, $summaries[0]->getTotalCustomers());
    }

    public function testProcessCalculatesActiveCustomers(): void
    {
        $order1 = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order1]);

        $order2 = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order2]);

        $this->handler->process();

        $summaries = $this->em->getRepository(CustomerSalesSummary::class)->findBy([
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertSame(2, $summaries[0]->getActiveCustomers());
    }

    public function testProcessIdentifiesNewCustomers(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process();

        $summaries = $this->em->getRepository(CustomerSalesSummary::class)->findBy([
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertSame(1, $summaries[0]->getNewCustomers());
    }

    public function testProcessCalculatesRevenue(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->handler->process();

        $summaries = $this->em->getRepository(CustomerSalesSummary::class)->findBy([
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertNotSame('0.00', $summaries[0]->getTotalRevenue());
    }
}
