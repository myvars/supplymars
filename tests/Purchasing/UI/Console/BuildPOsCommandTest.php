<?php

namespace App\Tests\Purchasing\UI\Console;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class BuildPOsCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:build-purchase-orders');
        $this->commandTester = new CommandTester($command);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessWithPendingOrders(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->commandTester->execute(['order-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testNoCustomerOrdersToProcess(): void
    {
        $this->commandTester->execute(['order-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No customer orders to process', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testInvalidCountReturnsInvalid(): void
    {
        $this->commandTester->execute(['order-count' => 0]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('orderCount must be > 0', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testDryRunDoesNotPersistChanges(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->commandTester->execute([
            'order-count' => 10,
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());

        // Order should have no POs (dry run)
        self::assertCount(0, $order->getPurchaseOrders());
    }

    #[WithStory(StaffUserStory::class)]
    public function testMultiSupplierAllocation(): void
    {
        $supplier1 = SupplierFactory::createOne(['isActive' => true]);
        $supplier2 = SupplierFactory::createOne(['isActive' => true]);

        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);

        SupplierProductFactory::createOne([
            'supplier' => $supplier1,
            'product' => $product1,
            'stock' => 100,
        ]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier2,
            'product' => $product2,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 5,
        ]);
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product2,
            'quantity' => 3,
        ]);

        $this->commandTester->execute(['order-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        // Both products should be allocated (possibly to different suppliers)
        self::assertGreaterThanOrEqual(1, $order->getPurchaseOrders()->count());
    }

    #[WithStory(StaffUserStory::class)]
    public function testVerboseOutputShowsProcessedOrderIds(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->commandTester->execute(
            ['order-count' => 10],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed Order IDs', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testOutputShowsPOsCreatedAndItemsAllocated(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->commandTester->execute(['order-count' => 10]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('PO(s)', $this->commandTester->getDisplay());
        self::assertStringContainsString('item(s)', $this->commandTester->getDisplay());
    }
}
