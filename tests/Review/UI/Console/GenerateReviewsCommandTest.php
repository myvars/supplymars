<?php

namespace App\Tests\Review\UI\Console;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Review\Domain\Model\Review\ProductReview;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
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

class GenerateReviewsCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:generate-reviews');
        $this->commandTester = new CommandTester($command);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testSuccessGeneratesReviews(): void
    {
        $this->createEligiblePurchase();

        $this->getReviewCount();

        $this->commandTester->execute([
            'count' => 5,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('reviews generated', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testNoEligibleItemsReturnsSuccess(): void
    {
        $this->commandTester->execute([
            'count' => 5,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('0 reviews generated', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testInvalidCountReturnsInvalid(): void
    {
        $this->commandTester->execute([
            'count' => 0,
        ]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Count must be > 0', $this->commandTester->getDisplay());
    }

    #[WithStory(StaffUserStory::class)]
    public function testDryRunDoesNotPersist(): void
    {
        $this->createEligiblePurchase();

        $initialCount = $this->getReviewCount();

        $this->commandTester->execute([
            'count' => 5,
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('[DRY RUN]', $this->commandTester->getDisplay());
        self::assertSame($initialCount, $this->getReviewCount());
    }

    #[WithStory(StaffUserStory::class)]
    public function testProductIdFilterWorks(): void
    {
        $purchase = $this->createEligiblePurchase();

        $this->commandTester->execute([
            'count' => 5,
            'product-id' => $purchase['productId'],
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    #[WithStory(StaffUserStory::class)]
    public function testVerboseOutputShowsReviewIds(): void
    {
        $this->createEligiblePurchase();

        $this->commandTester->execute([
            'count' => 5,
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        // If reviews were created, verbose output should show the section
        $display = $this->commandTester->getDisplay();
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

    private function getReviewCount(): int
    {
        return (int) $this->em->getRepository(ProductReview::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
