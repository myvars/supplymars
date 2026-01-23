<?php

namespace App\Tests\Purchasing\Application\Handler\PurchaseOrderItem;

use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Purchasing\Application\Command\PurchaseOrderItem\CreatePurchaseOrderItem;
use App\Purchasing\Application\Handler\PurchaseOrderItem\CreatePurchaseOrderItemHandler;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CreatePurchaseOrderItemHandlerTest extends KernelTestCase
{
    use Factories;

    private CreatePurchaseOrderItemHandler $handler;

    private PurchaseOrderItemRepository $purchaseOrderItems;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreatePurchaseOrderItemHandler::class);
        $this->purchaseOrderItems = self::getContainer()->get(PurchaseOrderItemRepository::class);
    }

    public function testHandleCreatesPurchaseOrderItem(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
            'cost' => '15.50',
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $command = new CreatePurchaseOrderItem(
            id: $orderItem->getPublicId(),
            supplierProductId: $supplierProduct->getPublicId(),
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        $purchaseOrderItem = $this->purchaseOrderItems->getByPublicId($result->payload);
        self::assertNotNull($purchaseOrderItem);
        self::assertSame($supplierProduct->getId(), $purchaseOrderItem->getSupplierProduct()->getId());
        self::assertSame(5, $purchaseOrderItem->getQuantity());
        self::assertSame('15.50', $purchaseOrderItem->getPrice());
        self::assertSame(bcmul('5', '15.50', 2), $purchaseOrderItem->getTotalPrice());
        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrderItem->getStatus());
    }

    public function testHandleFailsWhenOrderItemNotFound(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'stock' => 100,
        ]);

        $command = new CreatePurchaseOrderItem(
            id: OrderItemPublicId::new(),
            supplierProductId: $supplierProduct->getPublicId(),
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order item not found', $result->message);
    }

    public function testHandleFailsWhenSupplierProductNotFound(): void
    {
        $product = ProductFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $command = new CreatePurchaseOrderItem(
            id: $orderItem->getPublicId(),
            supplierProductId: SupplierProductPublicId::new(),
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier product not found', $result->message);
    }

    public function testHandleFailsWhenSupplierProductNotSourceForProduct(): void
    {
        // Create two different suppliers with products
        $supplier1 = SupplierFactory::createOne(['isActive' => true]);
        SupplierFactory::createOne(['isActive' => true]);

        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);

        // Create supplier product for product2, but order item uses product1
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier1,
            'product' => $product2,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 5,
        ]);

        $command = new CreatePurchaseOrderItem(
            id: $orderItem->getPublicId(),
            supplierProductId: $supplierProduct->getPublicId(),
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier product source missing', $result->message);
    }

    public function testHandleFailsWhenOrderItemNotEditable(): void
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

        // Set order item to a non-editable status by cancelling it
        $orderItem->cancelItem();

        $command = new CreatePurchaseOrderItem(
            id: $orderItem->getPublicId(),
            supplierProductId: $supplierProduct->getPublicId(),
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Order item cannot be edited', $result->message);
    }
}
