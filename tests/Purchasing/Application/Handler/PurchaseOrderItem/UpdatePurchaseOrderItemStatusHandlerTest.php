<?php

namespace App\Tests\Purchasing\Application\Handler\PurchaseOrderItem;

use App\Purchasing\Application\Command\PurchaseOrderItem\UpdatePurchaseOrderItemStatus;
use App\Purchasing\Application\Handler\PurchaseOrderItem\UpdatePurchaseOrderItemStatusHandler;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

#[WithStory(StaffUserStory::class)]
final class UpdatePurchaseOrderItemStatusHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdatePurchaseOrderItemStatusHandler $handler;

    private PurchaseOrderItemRepository $purchaseOrderItems;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdatePurchaseOrderItemStatusHandler::class);
        $this->purchaseOrderItems = self::getContainer()->get(PurchaseOrderItemRepository::class);
    }

    public function testHandleUpdatesStatus(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 10,
        ]);

        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        // Status is PENDING by default, transition to PROCESSING
        $command = new UpdatePurchaseOrderItemStatus(
            id: $purchaseOrderItem->getPublicId(),
            purchaseOrderStatus: PurchaseOrderStatus::PROCESSING,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Purchase order item status updated.', $result->message);

        $persisted = $this->purchaseOrderItems->getByPublicId($purchaseOrderItem->getPublicId());
        self::assertSame(PurchaseOrderStatus::PROCESSING, $persisted->getStatus());
    }

    public function testFailsWhenPurchaseOrderItemNotFound(): void
    {
        $missingId = PurchaseOrderItemPublicId::new();

        $command = new UpdatePurchaseOrderItemStatus(
            id: $missingId,
            purchaseOrderStatus: PurchaseOrderStatus::PROCESSING,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Purchase order item not found.', $result->message);
    }

    public function testFailsWhenInvalidTransition(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 10,
        ]);

        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        // Status is PENDING by default, try to transition directly to DELIVERED (invalid)
        $command = new UpdatePurchaseOrderItemStatus(
            id: $purchaseOrderItem->getPublicId(),
            purchaseOrderStatus: PurchaseOrderStatus::DELIVERED,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('cannot be updated', $result->message);
    }

    public function testFailsWhenStatusCannotBeChangedFromDelivered(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 10,
        ]);

        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        // Transition through PROCESSING -> ACCEPTED -> SHIPPED -> DELIVERED to reach terminal status
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::ACCEPTED);
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::SHIPPED);
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::DELIVERED);

        // Try to change from DELIVERED (terminal status)
        $command = new UpdatePurchaseOrderItemStatus(
            id: $purchaseOrderItem->getPublicId(),
            purchaseOrderStatus: PurchaseOrderStatus::PROCESSING,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('cannot be updated', $result->message);
    }

    public function testFailsWhenStatusCannotBeChangedFromCancelled(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 10,
        ]);

        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        // Transition to CANCELLED (terminal status)
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::CANCELLED);

        // Try to change from CANCELLED
        $command = new UpdatePurchaseOrderItemStatus(
            id: $purchaseOrderItem->getPublicId(),
            purchaseOrderStatus: PurchaseOrderStatus::PROCESSING,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('cannot be updated', $result->message);
    }
}
