<?php

namespace App\Tests\Integration\Service\Dashboard;

use App\DTO\OrderSummaryReportDto;
use App\Service\Dashboard\ReportHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReportHandlerIntegrationTest extends KernelTestCase
{
    private ReportHandler $reportHandler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->reportHandler = static::getContainer()->get(ReportHandler::class);
    }

    public function testBuild(): void
    {
        $dto = new OrderSummaryReportDto();
        $result = $this->reportHandler->build('order-summary', $dto);

        $this->assertIsArray($result);
    }

    public function testReports(): void
    {
        $result = $this->reportHandler->reports();

        $this->assertIsIterable($result);
    }

    public function testHasReport(): void
    {
        $result = $this->reportHandler->hasReport('order-summary');

        $this->assertIsBool($result);
    }
}