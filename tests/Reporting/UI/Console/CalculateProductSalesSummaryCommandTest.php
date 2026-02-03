<?php

namespace App\Tests\Reporting\UI\Console;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Model\SalesType\ProductSalesSummary;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class CalculateProductSalesSummaryCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:calculate-product-sales-summary');
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
        $initialCount = $this->getProductSalesSummaryCount();

        $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        self::assertSame($initialCount, $this->getProductSalesSummaryCount());
    }

    #[WithStory(StaffUserStory::class)]
    public function testVerboseOutputShowsDurations(): void
    {
        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed Durations', $this->commandTester->getDisplay());
    }

    private function getProductSalesSummaryCount(): int
    {
        return (int) $this->em->getRepository(ProductSalesSummary::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.salesId)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
