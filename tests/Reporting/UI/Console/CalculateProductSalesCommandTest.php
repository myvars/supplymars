<?php

namespace App\Tests\Reporting\UI\Console;

use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class CalculateProductSalesCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:calculate-product-sales');
        $this->commandTester = new CommandTester($command);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessProcessesDays(): void
    {
        $this->commandTester->execute([
            'day-count' => 3,
            '--skip-summary' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed product sales data for 3 days', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testInvalidDayCountReturnsInvalid(): void
    {
        $this->commandTester->execute([
            'day-count' => 0,
        ]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('dayCount must be > 0', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testDryRunDoesNotPersist(): void
    {
        $initialCount = $this->getProductSalesCount();

        $this->commandTester->execute([
            'day-count' => 3,
            '--dry-run' => true,
            '--skip-summary' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        self::assertSame($initialCount, $this->getProductSalesCount());
    }

    #[WithStory(StaffUserStory::class)]
    public function testSkipSummaryPreventsChaining(): void
    {
        $this->commandTester->execute([
            'day-count' => 1,
            '--skip-summary' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringNotContainsString('product sales summary', strtolower($this->commandTester->getDisplay()));
    }

    #[WithStory(StaffUserStory::class)]
    public function testVerboseOutputShowsDates(): void
    {
        $this->commandTester->execute([
            'day-count' => 2,
            '--skip-summary' => true,
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed Dates', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testZeroOffsetTriggersSummary(): void
    {
        $this->commandTester->execute([
            'day-count' => 1,
            'day-offset' => 0,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('summary', strtolower($this->commandTester->getDisplay()));
    }

    private function getProductSalesCount(): int
    {
        return (int) $this->em->getRepository(ProductSales::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.dateString)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
