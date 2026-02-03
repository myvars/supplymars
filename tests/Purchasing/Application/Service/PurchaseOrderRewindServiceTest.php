<?php

namespace App\Tests\Purchasing\Application\Service;

use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Purchasing\Application\Service\PurchaseOrderRewindService;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Shared\Domain\Event\DomainEventType;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\StatusChangeLogFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class PurchaseOrderRewindServiceTest extends KernelTestCase
{
    use Factories;

    private PurchaseOrderRewindService $service;

    private StatusChangeLogRepository $statusChangeLogs;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $service = $container->get(PurchaseOrderRewindService::class);
        \assert($service instanceof PurchaseOrderRewindService);
        $this->service = $service;

        $statusChangeLogs = $container->get(StatusChangeLogRepository::class);
        \assert($statusChangeLogs instanceof StatusChangeLogRepository);
        $this->statusChangeLogs = $statusChangeLogs;

        $em = $container->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $this->em = $em;
    }

    #[WithStory(StaffUserStory::class)]
    public function testRewindsProcessingPurchaseOrderToPending(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct,
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        $this->em->flush();

        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrder->getStatus());
        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrderItem->getStatus());

        $this->service->rewind($purchaseOrder);

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrderItem->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testRewindClearsDeliveredAtDate(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct,
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::DELIVERED);

        $this->em->flush();

        self::assertNotNull($purchaseOrderItem->getDeliveredAt());

        $this->service->rewind($purchaseOrder);

        self::assertNull($purchaseOrderItem->getDeliveredAt());
        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrderItem->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testRewindRemovesStatusChangeLogs(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct,
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            'eventTypeId' => $purchaseOrder->getId(),
            'status' => 'PROCESSING',
        ]);
        StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
            'eventTypeId' => $purchaseOrderItem->getId(),
            'status' => 'PROCESSING',
        ]);

        $poLogs = $this->statusChangeLogs->findByEvent(
            DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            $purchaseOrder->getId()
        );
        $poItemLogs = $this->statusChangeLogs->findByEvent(
            DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
            $purchaseOrderItem->getId()
        );

        self::assertCount(1, $poLogs);
        self::assertCount(1, $poItemLogs);

        $this->service->rewind($purchaseOrder);

        $poLogsAfter = $this->statusChangeLogs->findByEvent(
            DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            $purchaseOrder->getId()
        );
        $poItemLogsAfter = $this->statusChangeLogs->findByEvent(
            DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
            $purchaseOrderItem->getId()
        );

        self::assertCount(0, $poLogsAfter);
        self::assertCount(0, $poItemLogsAfter);
    }

    #[WithStory(StaffUserStory::class)]
    public function testRewindRegeneratesCustomerOrderStatus(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct,
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);

        $this->em->flush();

        self::assertSame(OrderStatus::SHIPPED, $order->getStatus());
        self::assertSame(OrderStatus::SHIPPED, $orderItem->getStatus());

        $this->service->rewind($purchaseOrder);

        // After rewind, PO items are PENDING, which maps to Order PENDING
        self::assertSame(OrderStatus::PENDING, $order->getStatus());
        self::assertSame(OrderStatus::PENDING, $orderItem->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testRewindsAllItemsInPurchaseOrder(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct1 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product1,
            'stock' => 100,
        ]);
        $supplierProduct2 = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product2,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem1 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 3,
        ]);
        $orderItem2 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product2,
            'quantity' => 2,
        ]);

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $purchaseOrderItem1 = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem1,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct1,
            'product' => $product1,
            'supplier' => $supplier,
            'quantity' => 3,
        ]);

        $purchaseOrderItem2 = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem2,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct2,
            'product' => $product2,
            'supplier' => $supplier,
            'quantity' => 2,
        ]);

        $purchaseOrderItem1->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $purchaseOrderItem2->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $this->em->flush();

        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrderItem1->getStatus());
        self::assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrderItem2->getStatus());

        $this->service->rewind($purchaseOrder);

        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrderItem1->getStatus());
        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrderItem2->getStatus());
    }
}
