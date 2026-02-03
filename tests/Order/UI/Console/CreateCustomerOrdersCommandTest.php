<?php

namespace App\Tests\Order\UI\Console;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class CreateCustomerOrdersCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:create-customer-orders');
        $this->commandTester = new CommandTester($command);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessCreatesOrders(): void
    {
        $this->createTestProducts(5);

        $initialCount = $this->getOrderCount();

        $this->commandTester->execute([
            'order-count' => 2,
            '--skip-timing' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Created 2 customer orders', $this->commandTester->getDisplay());
        self::assertSame($initialCount + 2, $this->getOrderCount());
    }

    #[WithStory(StaffUserStory::class)]
    public function testInvalidCountReturnsInvalid(): void
    {
        $this->commandTester->execute([
            'order-count' => 0,
        ]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Order count must be > 0', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testRandomOptionWorks(): void
    {
        $this->createTestProducts(5);

        $this->commandTester->execute([
            'order-count' => 1,
            '--random' => true,
            '--skip-timing' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    #[WithStory(StaffUserStory::class)]
    public function testDryRunDoesNotPersist(): void
    {
        $this->createTestProducts(5);

        $initialCount = $this->getOrderCount();

        $this->commandTester->execute([
            'order-count' => 3,
            '--dry-run' => true,
            '--skip-timing' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        self::assertSame($initialCount, $this->getOrderCount());
    }

    #[WithStory(StaffUserStory::class)]
    public function testVerboseOutputShowsOrderIds(): void
    {
        $this->createTestProducts(5);

        $this->commandTester->execute([
            'order-count' => 1,
            '--skip-timing' => true,
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Created Order IDs', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testSkipTimingSkipsSleep(): void
    {
        $this->createTestProducts(5);

        $start = microtime(true);
        $this->commandTester->execute([
            'order-count' => 1,
            '--skip-timing' => true,
        ]);
        $elapsed = microtime(true) - $start;

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertLessThan(5, $elapsed, 'Command should complete quickly with --skip-timing');
    }

    private function createTestProducts(int $count): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        for ($i = 0; $i < $count; ++$i) {
            $product = ProductFactory::createOne(['isActive' => true]);
            SupplierProductFactory::createOne([
                'supplier' => $supplier,
                'product' => $product,
                'stock' => 100,
            ]);
        }

        UserFactory::createOne();
    }

    private function getOrderCount(): int
    {
        return (int) $this->em->getRepository(CustomerOrder::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
