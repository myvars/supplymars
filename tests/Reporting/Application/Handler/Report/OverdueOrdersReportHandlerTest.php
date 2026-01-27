<?php

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Reporting\Application\Handler\Report\OverdueOrdersReportHandler;
use App\Reporting\Application\Report\OverdueOrderReportCriteria;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class OverdueOrdersReportHandlerTest extends KernelTestCase
{
    use Factories;

    private OverdueOrdersReportHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(OverdueOrdersReportHandler::class);
    }

    public function testInvokeReturnsResultWithExpectedKeys(): void
    {
        $criteria = new OverdueOrderReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertArrayHasKey('summary', $result->payload);
        self::assertArrayHasKey('overdue', $result->payload);
    }

    public function testInvokeReturnsPaginatedResults(): void
    {
        $criteria = new OverdueOrderReportCriteria();

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertInstanceOf(Pagerfanta::class, $result->payload['overdue']);
    }

    public function testInvokeHandlesOutOfRangePageGracefully(): void
    {
        $criteria = new OverdueOrderReportCriteria();
        $criteria->setPage(999);

        $result = ($this->handler)($criteria);

        self::assertTrue($result->ok);
        self::assertInstanceOf(Pagerfanta::class, $result->payload['overdue']);
        self::assertSame(1, $result->payload['overdue']->getCurrentPage());
    }
}
