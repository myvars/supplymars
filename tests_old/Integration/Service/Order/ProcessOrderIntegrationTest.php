<?php

namespace App\Tests\Integration\Service\Order;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\ProcessOrder;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use tests\Shared\Factory\SupplierProductFactory;
use Zenstruck\Foundry\Test\Factories;

class ProcessOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProcessOrder $processOrder;

    protected function setUp(): void
    {
        self::bootKernel();
        $createPurchaseOrderItem = static::getContainer()->get(CreatePurchaseOrderItem::class);
        $changePurchaseOrderItemStatus = static::getContainer()->get(ChangePurchaseOrderItemStatus::class);
        $this->processOrder = new ProcessOrder($createPurchaseOrderItem, $changePurchaseOrderItemStatus);
        StaffUserStory::load();
    }

    public function testHandleWithValidCustomerOrder(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()]);

        $context = new CrudContext();
        $context->setEntity($customerOrderItem->getCustomerOrder());

        ($this->processOrder)($context);

        $this->assertSame(1, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PROCESSING, $customerOrderItem->getCustomerOrder()->getStatus());
    }

    public function testHandleWithHalfProcessedCustomerOrder(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $customerOrder = $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder();

        $supplierProduct = SupplierProductFactory::createOne();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $supplierProduct->getProduct(),
        ]);

        $context = new CrudContext();
        $context->setEntity($customerOrderItem->getCustomerOrder());

        $this->assertSame(OrderStatus::PENDING, $customerOrder->getStatus());

        ($this->processOrder)($context);

        $this->assertSame(1, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PROCESSING, $customerOrder->getStatus());
    }

    public function testHandleWithMissingSupplierProduct(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne();

        $context = new CrudContext();
        $context->setEntity($customerOrderItem->getCustomerOrder());

        ($this->processOrder)($context);

        $this->assertSame(0, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PENDING, $customerOrderItem->getCustomerOrder()->getStatus());
    }

    public function testHandleWithInsufficientSupplierStock(): void
    {
        $product = ProductFactory::createOne(['stock' => 1]);
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => $product,
            'stock' => 1,
        ]);
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'product' => $supplierProduct->getProduct(),
            'quantity' => 2,
        ]);

        $context = new CrudContext();
        $context->setEntity($customerOrderItem->getCustomerOrder());

        ($this->processOrder)($context);

        $this->assertSame(0, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PENDING, $customerOrderItem->getCustomerOrder()->getStatus());
    }

    public function testHandleWithInactiveSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['isActive' => false]);
        $customerOrderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()]);

        $context = new CrudContext();
        $context->setEntity($customerOrderItem->getCustomerOrder());

        ($this->processOrder)($context);

        $this->assertSame(0, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PENDING, $customerOrderItem->getCustomerOrder()->getStatus());
    }
}
