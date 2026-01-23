<?php

namespace App\Tests\Reporting\Application\Handler;

use App\Reporting\Application\Handler\CalculateOrderSalesSummaryHandler;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use App\Reporting\Domain\Model\SalesType\OrderSalesType;
use App\Tests\Shared\Factory\OrderSalesFactory;
use App\Tests\Shared\Factory\OrderSalesSummaryFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CalculateOrderSalesSummaryHandlerTest extends KernelTestCase
{
    use Factories;

    private CalculateOrderSalesSummaryHandler $handler;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CalculateOrderSalesSummaryHandler::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProcessCreatesAllDurationSummaries(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $weekAgo = new \DateTime()->modify('-7 days')->format('Y-m-d');

        OrderSalesFactory::createOne([
            'dateString' => $today,
            'orderCount' => 10,
            'orderValue' => '500.00',
        ]);

        OrderSalesFactory::createOne([
            'dateString' => $weekAgo,
            'orderCount' => 5,
            'orderValue' => '250.00',
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(OrderSalesSummary::class)->findAll();
        $durations = array_map(fn (OrderSalesSummary $s): ?SalesDuration => $s->getDuration(), $summaries);

        foreach (SalesDuration::cases() as $duration) {
            self::assertContains($duration, $durations, 'Missing duration: ' . $duration->value);
        }
    }

    public function testProcessWithRebuildReplacesExistingData(): void
    {
        $today = new \DateTime()->format('Y-m-d');

        OrderSalesSummaryFactory::createOne([
            'orderSalesType' => OrderSalesType::create(SalesDuration::LAST_30),
            'dateString' => SalesDuration::LAST_30->getStartDate(),
            'orderCount' => 999,
            'orderValue' => 9999.00,
        ]);

        $this->em->clear();

        OrderSalesFactory::createOne([
            'dateString' => $today,
            'orderCount' => 5,
            'orderValue' => '250.00',
        ]);

        $this->handler->process(rebuild: true);

        $summaries = $this->em->getRepository(OrderSalesSummary::class)->findBy([
            'duration' => SalesDuration::LAST_30,
        ]);

        self::assertCount(1, $summaries);
        self::assertNotSame(999, $summaries[0]->getOrderCount());
    }

    public function testProcessWithNoSourceDataCreatesNoSummaries(): void
    {
        $this->handler->process();

        $summaries = $this->em->getRepository(OrderSalesSummary::class)->findAll();
        self::assertCount(0, $summaries);
    }

    public function testProcessCorrectlyAggregatesLast7Days(): void
    {
        $now = new \DateTime();

        for ($i = 0; $i < 7; ++$i) {
            $date = (clone $now)->modify(sprintf('-%d days', $i))->format('Y-m-d');
            OrderSalesFactory::createOne([
                'dateString' => $date,
                'orderCount' => 10,
                'orderValue' => '100.00',
            ]);
        }

        $this->handler->process();

        $summaries = $this->em->getRepository(OrderSalesSummary::class)->findBy([
            'duration' => SalesDuration::LAST_7,
        ]);

        self::assertCount(1, $summaries);
        self::assertSame(70, $summaries[0]->getOrderCount());
        self::assertSame('700.00', $summaries[0]->getOrderValue());
    }

    public function testProcessCorrectlyAggregatesLast30Days(): void
    {
        $now = new \DateTime();

        for ($i = 0; $i < 30; ++$i) {
            $date = (clone $now)->modify(sprintf('-%d days', $i))->format('Y-m-d');
            OrderSalesFactory::createOne([
                'dateString' => $date,
                'orderCount' => 1,
                'orderValue' => '10.00',
            ]);
        }

        $this->handler->process();

        $summaries = $this->em->getRepository(OrderSalesSummary::class)->findBy([
            'duration' => SalesDuration::LAST_30,
        ]);

        self::assertCount(1, $summaries);
        self::assertSame(30, $summaries[0]->getOrderCount());
        self::assertSame('300.00', $summaries[0]->getOrderValue());
    }

    public function testProcessGroupsByDateStringForDayDuration(): void
    {
        $now = new \DateTime();

        for ($i = 0; $i < 5; ++$i) {
            $date = (clone $now)->modify(sprintf('-%d days', $i))->format('Y-m-d');
            OrderSalesFactory::createOne([
                'dateString' => $date,
                'orderCount' => 10,
                'orderValue' => '100.00',
            ]);
        }

        $this->handler->process();

        $summaries = $this->em->getRepository(OrderSalesSummary::class)->findBy([
            'duration' => SalesDuration::DAY,
        ]);

        self::assertCount(1, $summaries);
    }

    public function testProcessGroupsByMonthForMonthDuration(): void
    {
        $firstOfMonth = new \DateTime()->format('Y-m-01');

        OrderSalesFactory::createOne([
            'dateString' => $firstOfMonth,
            'orderCount' => 50,
            'orderValue' => '500.00',
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(OrderSalesSummary::class)->findBy([
            'duration' => SalesDuration::MONTH,
        ]);

        self::assertCount(1, $summaries);
        self::assertStringEndsWith('-01', $summaries[0]->getDateString());
    }

    public function testProcessCalculatesCorrectAverageOrderValue(): void
    {
        $today = new \DateTime()->format('Y-m-d');

        OrderSalesFactory::createOne([
            'dateString' => $today,
            'orderCount' => 4,
            'orderValue' => '100.00',
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(OrderSalesSummary::class)->findBy([
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertEqualsWithDelta(25.0, (float) $summaries[0]->getAverageOrderValue(), 0.01);
    }
}
