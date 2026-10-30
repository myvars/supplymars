<?php

declare(strict_types=1);

namespace App\Tests\Reporting\UI\Console;

use App\Tests\Shared\Factory\ProductSalesFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CalculateProductSalesCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:calculate-product-sales');
        $this->commandTester = new CommandTester($command);
    }

    public function testSuccessProcessesDays(): void
    {
        $this->commandTester->execute([
            'day-count' => 3,
            '--skip-summary' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed product sales data for 3 days', $this->commandTester->getDisplay());
    }

    public function testInvalidDayCountReturnsInvalid(): void
    {
        $this->commandTester->execute([
            'day-count' => 0,
        ]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('dayCount must be > 0', $this->commandTester->getDisplay());
    }

    public function testDryRunDoesNotPersist(): void
    {
        $initialCount = ProductSalesFactory::count();

        $this->commandTester->execute([
            'day-count' => 3,
            '--dry-run' => true,
            '--skip-summary' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        ProductSalesFactory::assert()->count($initialCount);
    }

    public function testSkipSummaryPreventsChaining(): void
    {
        $this->commandTester->execute([
            'day-count' => 1,
            '--skip-summary' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringNotContainsString('product sales summary', strtolower($this->commandTester->getDisplay()));
    }

    public function testVerboseOutputShowsDates(): void
    {
        $this->commandTester->execute([
            'day-count' => 2,
            '--skip-summary' => true,
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed Dates', $this->commandTester->getDisplay());
    }

    public function testZeroOffsetTriggersSummary(): void
    {
        $this->commandTester->execute([
            'day-count' => 1,
            'day-offset' => 0,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('summary', strtolower($this->commandTester->getDisplay()));
    }
}
