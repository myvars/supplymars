<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\Application\Handler\PurchaseOrder;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Purchasing\Application\Command\PurchaseOrder\RewindPurchaseOrder;
use App\Purchasing\Application\Handler\PurchaseOrder\RewindPurchaseOrderHandler;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Shared\Domain\Event\DomainEventType;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class RewindPurchaseOrderHandlerTest extends KernelTestCase
{
    private RewindPurchaseOrderHandler $handler;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $handler = $container->get(RewindPurchaseOrderHandler::class);
        \assert($handler instanceof RewindPurchaseOrderHandler);
        $this->handler = $handler;

        $em = $container->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $this->em = $em;
    }

    public function testRewindsExistingPurchaseOrder(): void
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

        $command = new RewindPurchaseOrder($purchaseOrder->getPublicId());
        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Purchase order rewound to pending.', $result->message);
        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
    }

    public function testRefusesPurchaseOrderWithRefundedItem(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);

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

        // Rejected and refunded, then the line allocated again on another supplier's PO
        $refundedItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'purchaseOrder' => $purchaseOrder,
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);
        $refundedItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $refundedItem->updateItemStatus(PurchaseOrderStatus::REJECTED);
        $refundedItem->updateItemStatus(PurchaseOrderStatus::REFUNDED);

        PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'product' => $product,
            'supplier' => SupplierFactory::createOne(['isActive' => true]),
            'quantity' => 5,
        ]);

        $this->em->flush();
        $logsBefore = count($this->statusLogs($refundedItem->getId()));

        $result = ($this->handler)(new RewindPurchaseOrder($purchaseOrder->getPublicId()));

        self::assertFalse($result->ok);
        self::assertSame('Cannot rewind: the purchase order has refunded or cancelled items.', $result->message);
        self::assertSame(PurchaseOrderStatus::REFUNDED, $refundedItem->getStatus());
        self::assertSame(PurchaseOrderStatus::REFUNDED, $purchaseOrder->getStatus());
        // The line stays on POs once, and the history is kept
        self::assertSame(5, $orderItem->getQtyAddedToPurchaseOrders());
        self::assertGreaterThan(0, $logsBefore);
        self::assertCount($logsBefore, $this->statusLogs($refundedItem->getId()));
    }

    public function testRefusesPurchaseOrderWithCancelledItem(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);

        $order = CustomerOrderFactory::createOne();
        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $cancelledItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'purchaseOrder' => $purchaseOrder,
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 1,
        ]);
        $cancelledItem->updateItemStatus(PurchaseOrderStatus::CANCELLED);

        $this->em->flush();

        $result = ($this->handler)(new RewindPurchaseOrder($purchaseOrder->getPublicId()));

        self::assertFalse($result->ok);
        self::assertSame('Cannot rewind: the purchase order has refunded or cancelled items.', $result->message);
        self::assertSame(PurchaseOrderStatus::CANCELLED, $cancelledItem->getStatus());
        self::assertSame(PurchaseOrderStatus::CANCELLED, $purchaseOrder->getStatus());
    }

    /** @return StatusChangeLog[] */
    private function statusLogs(?int $purchaseOrderItemId): array
    {
        return self::getContainer()->get(StatusChangeLogRepository::class)->findByEvent(
            DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
            (int) $purchaseOrderItemId
        );
    }

    public function testFailsWhenPurchaseOrderNotFound(): void
    {
        $missingId = PurchaseOrderPublicId::new();

        $command = new RewindPurchaseOrder($missingId);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Purchase order not found', $result->message);
    }
}
