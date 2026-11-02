<?php

declare(strict_types=1);

namespace App\Tests\Reporting\UI\Console;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Tests\Shared\Factory\OrderSalesSummaryFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CalculateOrderSalesSummaryCommandTest extends KernelTestCase
{
    public function testSuccessProcessesAllDurations(): void
    {
        $result = self::runCommand('app:calculate-order-sales-summary');

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString(
            sprintf('%d durations', count(SalesDuration::cases())),
            $result->getDisplay()
        );
    }

    public function testRebuildFlagWorks(): void
    {
        $result = self::runCommand('app:calculate-order-sales-summary', [
            'rebuild' => 1,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('calculation completed', $result->getDisplay());
    }

    public function testDryRunDoesNotPersist(): void
    {
        $initialCount = OrderSalesSummaryFactory::count();

        $result = self::runCommand('app:calculate-order-sales-summary', [
            '--dry-run' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('[DRY RUN]', $result->getDisplay());
        OrderSalesSummaryFactory::assert()->count($initialCount);
    }

    public function testVerboseOutputShowsDurations(): void
    {
        $result = self::runCommand('app:calculate-order-sales-summary', [], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed Durations', $result->getDisplay());
    }
}
