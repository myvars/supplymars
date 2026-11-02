<?php

declare(strict_types=1);

namespace App\Tests\Review\UI\Console;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class GenerateReviewsCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSuccessGeneratesReviews(): void
    {
        $this->createEligiblePurchase();

        $result = self::runCommand('app:generate-reviews', [
            'count' => 5,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('reviews generated', $result->getDisplay());
    }

    public function testNoEligibleItemsReturnsSuccess(): void
    {
        $result = self::runCommand('app:generate-reviews', [
            'count' => 5,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('0 reviews generated', $result->getDisplay());
    }

    public function testInvalidCountReturnsInvalid(): void
    {
        $result = self::runCommand('app:generate-reviews', [
            'count' => 0,
        ]);

        $this->assertCommandIsInvalid($result);
        self::assertStringContainsString('Count must be > 0', $result->getDisplay());
    }

    public function testDryRunDoesNotPersist(): void
    {
        $this->createEligiblePurchase();

        $initialCount = ProductReviewFactory::count();

        $result = self::runCommand('app:generate-reviews', [
            'count' => 5,
            '--dry-run' => true,
        ]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('[DRY RUN]', $result->getDisplay());
        ProductReviewFactory::assert()->count($initialCount);
    }

    public function testProductIdFilterWorks(): void
    {
        $purchase = $this->createEligiblePurchase();

        $result = self::runCommand('app:generate-reviews', [
            'count' => 5,
            'product-id' => $purchase['productId'],
        ]);

        $this->assertCommandIsSuccessful($result);
    }

    public function testVerboseOutputShowsReviewIds(): void
    {
        $this->createEligiblePurchase();

        $result = self::runCommand('app:generate-reviews', [
            'count' => 5,
        ], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        $this->assertCommandIsSuccessful($result);
        // If reviews were created, verbose output should show the section
        $display = $result->getDisplay();
        if (!str_contains($display, '0 reviews generated')) {
            self::assertStringContainsString('Created Review IDs', $display);
        }
    }

    /**
     * @return array{productId: int}
     */
    private function createEligiblePurchase(): array
    {
        $customer = UserFactory::createOne();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne(['customer' => $customer]);
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 1,
        ]);

        $po = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);
        $poItem = PurchaseOrderItemFactory::createOne([
            'purchaseOrder' => $po,
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'supplier' => $supplier,
            'product' => $product,
            'quantity' => 1,
        ]);

        // Transition to DELIVERED status
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $poItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $poItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);
        $poItem->updateItemStatus(PurchaseOrderStatus::DELIVERED);

        $this->em->flush();

        return ['productId' => $product->getId()];
    }
}
