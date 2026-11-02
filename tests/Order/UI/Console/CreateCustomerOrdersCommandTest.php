<?php

declare(strict_types=1);

namespace App\Tests\Order\UI\Console;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CreateCustomerOrdersCommandTest extends KernelTestCase
{
    public function testSuccessCreatesOrders(): void
    {
        $this->createTestProducts(5);

        $initialCount = CustomerOrderFactory::count();

        $result = self::runCommand('app:create-customer-orders', [
            'order-count' => 2,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Created 2 customer orders', $result->getDisplay());
        CustomerOrderFactory::assert()->count($initialCount + 2);
    }

    public function testInvalidCountReturnsInvalid(): void
    {
        $result = self::runCommand('app:create-customer-orders', [
            'order-count' => 0,
        ]);

        $this->assertCommandIsInvalid($result);
        self::assertStringContainsString('Order count must be > 0', $result->getDisplay());
    }

    public function testRandomOptionWorks(): void
    {
        $this->createTestProducts(5);

        $result = self::runCommand('app:create-customer-orders', [
            'order-count' => 1,
            '--random' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $this->createTestProducts(5);

        $initialCount = CustomerOrderFactory::count();

        $result = self::runCommand('app:create-customer-orders', [
            'order-count' => 3,
            '--dry-run' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('[DRY RUN]', $result->getDisplay());
        CustomerOrderFactory::assert()->count($initialCount);
    }

    public function testVerboseOutputShowsOrderIds(): void
    {
        $this->createTestProducts(5);

        $result = self::runCommand('app:create-customer-orders', [
            'order-count' => 1,
        ], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Created Order IDs', $result->getDisplay());
    }

    public function testCreatesOneOrderByDefault(): void
    {
        $this->createTestProducts(5);

        $initialCount = CustomerOrderFactory::count();

        $result = self::runCommand('app:create-customer-orders');

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Created 1 customer orders', $result->getDisplay());
        CustomerOrderFactory::assert()->count($initialCount + 1);
    }

    public function testHasNoTimingOption(): void
    {
        // The command never waits: the schedule (MainSchedule) spreads the orders instead
        $this->expectException(InvalidOptionException::class);

        self::runCommand('app:create-customer-orders', [
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
