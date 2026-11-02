<?php

declare(strict_types=1);

namespace App\Tests\Reporting\UI\Console;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerSalesFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CalculateCustomerSalesCommandTest extends KernelTestCase
{
    public function testSuccessProcessesDays(): void
    {
        CustomerOrderFactory::createOne();

        $result = self::runCommand('app:calculate-customer-sales', [
            'day-count' => 3,
            '--skip-summary' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed customer sales data for 3 days', $result->getDisplay());
    }

    public function testInvalidDayCountReturnsInvalid(): void
    {
        $result = self::runCommand('app:calculate-customer-sales', [
            'day-count' => 0,
        ]);

        $this->assertCommandIsInvalid($result);
        self::assertStringContainsString('dayCount must be > 0', $result->getDisplay());
    }

    public function testDryRunDoesNotPersist(): void
    {
        CustomerOrderFactory::createOne();

        $initialCount = CustomerSalesFactory::count();

        $result = self::runCommand('app:calculate-customer-sales', [
            'day-count' => 3,
            '--dry-run' => true,
            '--skip-summary' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('[DRY RUN]', $result->getDisplay());
        CustomerSalesFactory::assert()->count($initialCount);
    }

    public function testSkipSummaryPreventsChaining(): void
    {
        $result = self::runCommand('app:calculate-customer-sales', [
            'day-count' => 1,
            '--skip-summary' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringNotContainsString('customer sales summary', strtolower($result->getDisplay()));
    }

    public function testVerboseOutputShowsDates(): void
    {
        $result = self::runCommand('app:calculate-customer-sales', [
            'day-count' => 2,
            '--skip-summary' => true,
        ], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed Dates', $result->getDisplay());
    }

    public function testZeroOffsetTriggersSummary(): void
    {
        CustomerOrderFactory::createOne();

        $result = self::runCommand('app:calculate-customer-sales', [
            'day-count' => 1,
            'day-offset' => 0,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('summary', strtolower($result->getDisplay()));
    }
}
