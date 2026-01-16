<?php

namespace App\Tests\Reporting\Integration;

use App\Reporting\Application\Report\OrderSummaryReportCriteria;
use App\Reporting\Domain\Metric\OrderSalesMetric;
use App\Reporting\Domain\Metric\SalesDuration;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderSummaryReportDtoIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidOrderSummaryReportDto(): void
    {
        $dto = new OrderSummaryReportCriteria();
        $dto->setSort(OrderSalesMetric::VALUE->value);
        $dto->setSortDirection('ASC');
        $dto->setDuration(SalesDuration::LAST_7->value);

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors);
    }
}
