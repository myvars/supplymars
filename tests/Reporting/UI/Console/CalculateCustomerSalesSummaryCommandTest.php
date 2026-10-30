<?php

declare(strict_types=1);

namespace App\Tests\Reporting\UI\Console;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Tests\Shared\Factory\CustomerSalesSummaryFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CalculateCustomerSalesSummaryCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:calculate-customer-sales-summary');
        $this->commandTester = new CommandTester($command);
    }

    public function testSuccessProcessesAllDurations(): void
    {
        $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            sprintf('%d durations', count(SalesDuration::cases())),
            $this->commandTester->getDisplay()
        );
    }

    public function testRebuildFlagWorks(): void
    {
        $this->commandTester->execute([
            'rebuild' => 1,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('calculation completed', $this->commandTester->getDisplay());
    }

    public function testDryRunDoesNotPersist(): void
    {
        $initialCount = CustomerSalesSummaryFactory::count();

        $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        CustomerSalesSummaryFactory::assert()->count($initialCount);
    }

    public function testVerboseOutputShowsDurations(): void
    {
        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed Durations', $this->commandTester->getDisplay());
    }
}
