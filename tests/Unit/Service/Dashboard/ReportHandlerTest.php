<?php

namespace App\Tests\Unit\Service\Dashboard;

use App\Service\Dashboard\ReportHandler;
use App\Service\Dashboard\Report\ReportInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ServiceCollectionInterface;

class ReportHandlerTest extends TestCase
{
    private ServiceCollectionInterface $serviceCollectionMock;
    private ReportHandler $reportHandler;

    protected function setUp(): void
    {
        $this->serviceCollectionMock = $this->createMock(ServiceCollectionInterface::class);
        $this->reportHandler = new ReportHandler($this->serviceCollectionMock);
    }

    public function testBuild(): void
    {
        $reportMock = $this->createMock(ReportInterface::class);
        $dto = new \stdClass();

        $this->serviceCollectionMock->method('get')->with('test_report')->willReturn($reportMock);
        $reportMock->method('build')->with($dto)->willReturn(['data' => 'test']);

        $result = $this->reportHandler->build('test_report', $dto);

        $this->assertSame(['data' => 'test'], $result);
    }

    public function testReports(): void
    {
        $this->serviceCollectionMock->method('getProvidedServices')->willReturn(['report1' => ReportInterface::class, 'report2' => ReportInterface::class]);

        $result = $this->reportHandler->reports();

        $this->assertSame(['report1', 'report2'], $result);
    }

    public function testHasReport(): void
    {
        $this->serviceCollectionMock->method('has')->with('test_report')->willReturn(true);

        $result = $this->reportHandler->hasReport('test_report');

        $this->assertTrue($result);
    }
}