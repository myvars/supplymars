<?php

declare(strict_types=1);

namespace App\Tests\Order\UI\Console;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CreateCustomerOrdersCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:create-customer-orders');
        $this->commandTester = new CommandTester($command);
    }

    public function testSuccessCreatesOrders(): void
    {
        $this->createTestProducts(5);

        $initialCount = CustomerOrderFactory::count();

        $this->commandTester->execute([
            'order-count' => 2,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Created 2 customer orders', $this->commandTester->getDisplay());
        CustomerOrderFactory::assert()->count($initialCount + 2);
    }

    public function testInvalidCountReturnsInvalid(): void
    {
        $this->commandTester->execute([
            'order-count' => 0,
        ]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Order count must be > 0', $this->commandTester->getDisplay());
    }

    public function testRandomOptionWorks(): void
    {
        $this->createTestProducts(5);

        $this->commandTester->execute([
            'order-count' => 1,
            '--random' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testDryRunDoesNotPersist(): void
    {
        $this->createTestProducts(5);

        $initialCount = CustomerOrderFactory::count();

        $this->commandTester->execute([
            'order-count' => 3,
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        CustomerOrderFactory::assert()->count($initialCount);
    }

    public function testVerboseOutputShowsOrderIds(): void
    {
        $this->createTestProducts(5);

        $this->commandTester->execute([
            'order-count' => 1,
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Created Order IDs', $this->commandTester->getDisplay());
    }

    public function testCreatesOneOrderByDefault(): void
    {
        $this->createTestProducts(5);

        $initialCount = CustomerOrderFactory::count();

        $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Created 1 customer orders', $this->commandTester->getDisplay());
        CustomerOrderFactory::assert()->count($initialCount + 1);
    }

    public function testHasNoTimingOption(): void
    {
        // The command never waits: the schedule (MainSchedule) spreads the orders instead
        $this->expectException(InvalidOptionException::class);

        $this->commandTester->execute([
            'order-count' => 1,
            '--skip-timing' => true,
        ]);
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
}
