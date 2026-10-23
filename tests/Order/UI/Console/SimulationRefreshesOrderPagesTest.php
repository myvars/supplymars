<?php

declare(strict_types=1);

namespace App\Tests\Order\UI\Console;

use App\Order\Application\Message\RefreshOrderPage;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Shared\Infrastructure\Notification\MercureLiveUpdatePublisher;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Mercure\QueuedNotifications;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mercure\Update;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * The simulation's commands change many purchase order items in one flush. Each order they touch gets one
 * refresh of its page (RefreshOrderPageForViewers), handled as the worker would.
 */
#[WithStory(StaffUserStory::class)]
class SimulationRefreshesOrderPagesTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testAcceptingPurchaseOrdersRefreshesEachOrderOnce(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $orders = [$this->orderWithItems($supplier, 3, PurchaseOrderStatus::PROCESSING), $this->orderWithItems($supplier, 2, PurchaseOrderStatus::PROCESSING)];

        $this->assertEachRefreshedOnce($orders, 'app:accept-purchase-orders', ['po-count' => 10, '--supplier' => $supplier->getId()]);
    }

    public function testShippingRefreshesEachOrderOnce(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $orders = [$this->orderWithItems($supplier, 3, PurchaseOrderStatus::ACCEPTED), $this->orderWithItems($supplier, 2, PurchaseOrderStatus::ACCEPTED)];

        $this->assertEachRefreshedOnce($orders, 'app:ship-purchase-order-items', ['po-item-count' => 10, '--supplier' => $supplier->getId(), '--skip-timing' => true]);
    }

    public function testDeliveringRefreshesEachOrderOnce(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $orders = [$this->orderWithItems($supplier, 3, PurchaseOrderStatus::SHIPPED), $this->orderWithItems($supplier, 2, PurchaseOrderStatus::SHIPPED)];

        $this->assertEachRefreshedOnce($orders, 'app:deliver-purchase-order-items', ['po-item-count' => 10, '--supplier' => $supplier->getId(), '--skip-timing' => true]);
    }

    /**
     * @param list<CustomerOrder>  $orders
     * @param array<string, mixed> $input
     */
    private function assertEachRefreshedOnce(array $orders, string $command, array $input): void
    {
        $container = self::getContainer();
        // What setting up queued, so the command's own refreshes are not held back as duplicates
        QueuedNotifications::handle($container);
        $before = count(QueuedNotifications::published($container));

        $tester = new CommandTester(new Application(self::$kernel)->find($command));
        $tester->execute($input);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());

        $handled = QueuedNotifications::handle($container);
        self::assertCount(count($orders), $handled, $tester->getDisplay());
        self::assertContainsOnlyInstancesOf(RefreshOrderPage::class, $handled);

        $updates = array_slice(QueuedNotifications::published($container), $before);
        self::assertEqualsCanonicalizing(
            array_map(static fn (CustomerOrder $order): array => ['order/' . $order->getPublicId()->value()], $orders),
            array_map(static fn (Update $update): array => $update->getTopics(), $updates),
        );
        foreach ($updates as $update) {
            self::assertTrue($update->isPrivate());
            self::assertSame(MercureLiveUpdatePublisher::REFRESH_STREAM, $update->getData());
        }
    }

    /** An order whose items are on one purchase order from $supplier, each moved on to $status. */
    private function orderWithItems(Supplier $supplier, int $itemCount, PurchaseOrderStatus $status): CustomerOrder
    {
        $order = CustomerOrderFactory::createOne();
        $po = PurchaseOrderFactory::createOne(['customerOrder' => $order, 'supplier' => $supplier]);
        $poItems = [];

        for ($i = 0; $i < $itemCount; ++$i) {
            $product = ProductFactory::createOne(['isActive' => true]);
            SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => $product, 'stock' => 100]);
            $poItems[] = PurchaseOrderItemFactory::createOne([
                'purchaseOrder' => $po,
                'customerOrder' => $order,
                'customerOrderItem' => CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product, 'quantity' => 2]),
                'supplier' => $supplier,
                'product' => $product,
                'quantity' => 2,
            ]);
        }

        // Once every item is on the order, which then takes no more
        $path = [PurchaseOrderStatus::PROCESSING, PurchaseOrderStatus::ACCEPTED, PurchaseOrderStatus::SHIPPED];
        foreach ($poItems as $poItem) {
            foreach (array_slice($path, 0, array_search($status, $path, true) + 1) as $next) {
                $poItem->updateItemStatus($next);
            }
        }

        $this->em->flush();

        return $order;
    }
}
