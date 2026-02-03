<?php

namespace App\Tests\Reporting\UI\Console;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\OrderSalesSummary;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class CalculateOrderSalesSummaryCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:calculate-order-sales-summary');
        $this->commandTester = new CommandTester($command);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessProcessesAllDurations(): void
    {
        $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            sprintf('%d durations', count(SalesDuration::cases())),
            $this->commandTester->getDisplay()
        );
    }

    #[WithStory(StaffUserStory::class)]
    public function testRebuildFlagWorks(): void
    {
        $this->commandTester->execute([
            'rebuild' => 1,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('calculation completed', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testDryRunDoesNotPersist(): void
    {
        $initialCount = $this->getOrderSalesSummaryCount();

        $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        self::assertSame($initialCount, $this->getOrderSalesSummaryCount());
    }

    #[WithStory(StaffUserStory::class)]
    public function testVerboseOutputShowsDurations(): void
    {
        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed Durations', $this->commandTester->getDisplay());
    }

    private function getOrderSalesSummaryCount(): int
    {
        return (int) $this->em->getRepository(OrderSalesSummary::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.dateString)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
