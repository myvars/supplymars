<?php

namespace App\Tests\Purchasing\Application\Handler\PurchaseOrderItem;

use App\Purchasing\Application\Command\PurchaseOrderItem\UpdatePurchaseOrderItemQuantity;
use App\Purchasing\Application\Handler\PurchaseOrderItem\UpdatePurchaseOrderItemQuantityHandler;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
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
final class UpdatePurchaseOrderItemQuantityHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdatePurchaseOrderItemQuantityHandler $handler;

    private PurchaseOrderItemRepository $purchaseOrderItems;

    private PurchaseOrderRepository $purchaseOrders;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdatePurchaseOrderItemQuantityHandler::class);
        $this->purchaseOrderItems = self::getContainer()->get(PurchaseOrderItemRepository::class);
        $this->purchaseOrders = self::getContainer()->get(PurchaseOrderRepository::class);
    }

    public function testHandleUpdatesPurchaseOrderItemQuantity(): void
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
            'cost' => '5.00',
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        $command = new UpdatePurchaseOrderItemQuantity(
            id: $purchaseOrderItem->getPublicId(),
            quantity: 5,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Order item updated', $result->message);

        $persisted = $this->purchaseOrderItems->getByPublicId($purchaseOrderItem->getPublicId());
        self::assertSame(5, $persisted->getQuantity());
        self::assertSame('25.00', $persisted->getTotalPrice());
    }

    public function testFailsWhenPurchaseOrderItemNotFound(): void
    {
        $missingId = PurchaseOrderItemPublicId::new();

        $command = new UpdatePurchaseOrderItemQuantity(
            id: $missingId,
            quantity: 5,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Purchase order item not found.', $result->message);
    }

    public function testZeroQuantityRemovesPurchaseOrderItem(): void
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

        // Create two PO items so the PO is not empty after removal
        $purchaseOrderItem1 = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        $purchaseOrder = $purchaseOrderItem1->getPurchaseOrder();

        // Create a second customer order item for second PO item
        $customerOrderItem2 = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 5,
        ]);

        PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem2,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'purchaseOrder' => $purchaseOrder,
            'quantity' => 2,
        ]);

        $publicId = $purchaseOrderItem1->getPublicId();
        $purchaseOrderPublicId = $purchaseOrder->getPublicId();

        $command = new UpdatePurchaseOrderItemQuantity(
            id: $publicId,
            quantity: 0,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Purchase order item removed', $result->message);

        $removed = $this->purchaseOrderItems->getByPublicId($publicId);
        self::assertNull($removed);

        // Purchase order should still exist (has other items)
        $remainingPO = $this->purchaseOrders->getByPublicId($purchaseOrderPublicId);
        self::assertNotNull($remainingPO);
    }

    public function testZeroQuantityRemovesEmptyPurchaseOrder(): void
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

        // Create single PO item
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $customerOrder,
            'quantity' => 3,
        ]);

        $publicId = $purchaseOrderItem->getPublicId();
        $purchaseOrderPublicId = $purchaseOrderItem->getPurchaseOrder()->getPublicId();

        $command = new UpdatePurchaseOrderItemQuantity(
            id: $publicId,
            quantity: 0,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Purchase order item removed', $result->message);

        // Both item and PO should be removed
        $removedItem = $this->purchaseOrderItems->getByPublicId($publicId);
        self::assertNull($removedItem);

        $removedPO = $this->purchaseOrders->getByPublicId($purchaseOrderPublicId);
        self::assertNull($removedPO);
    }

    public function testFailsWhenItemNotEditable(): void
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

        // Move item status to PROCESSING so it can't be edited
        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        $command = new UpdatePurchaseOrderItemQuantity(
            id: $purchaseOrderItem->getPublicId(),
            quantity: 5,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('cannot be edited', $result->message);
    }

    public function testFailsWhenQuantityExceedsMax(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $customerOrder = CustomerOrderFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $product,
            'quantity' => 5, // Customer order item qty = 5
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
            'quantity' => 3, // PO item qty = 3, so max is 5 (outstanding = 2, max = 2 + 3 = 5)
        ]);

        // Try to update to 6, which exceeds max (5)
        $command = new UpdatePurchaseOrderItemQuantity(
            id: $purchaseOrderItem->getPublicId(),
            quantity: 6,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('cannot be greater than', $result->message);
    }
}
