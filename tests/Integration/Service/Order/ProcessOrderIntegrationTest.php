<?php

namespace App\Tests\Integration\Service\Order;

use App\Enum\OrderStatus;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\ProductFactory;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\SupplierProductFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\ProcessOrder;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use App\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $customerOrderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()])->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customerOrderItem->getCustomerOrder());

        $this->processOrder->handle($crudOptions);

        $this->assertSame(1, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PROCESSING, $customerOrderItem->getCustomerOrder()->getStatus());
    }

    public function testHandleWithHalfProcessedCustomerOrder(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne()->_real();
        $customerOrder = $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder();

        $supplierProduct = SupplierProductFactory::createOne()->_real();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $customerOrder,
            'product' => $supplierProduct->getProduct()
        ])->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customerOrderItem->getCustomerOrder());

        $this->assertSame(OrderStatus::PENDING, $customerOrder->getStatus());

        $this->processOrder->handle($crudOptions);

        $this->assertSame(1, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PROCESSING, $customerOrder->getStatus());
    }

    public function testHandleWithMissingSupplierProduct(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne()->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customerOrderItem->getCustomerOrder());

        $this->processOrder->handle($crudOptions);

        $this->assertSame(0, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PENDING, $customerOrderItem->getCustomerOrder()->getStatus());
    }

    public function testHandleWithInsufficientSupplierStock(): void
    {
        $product = ProductFactory::createOne(['stock' => 1])->_real();
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => $product,
            'stock' => 1
        ])->_real();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'product' => $supplierProduct->getProduct(),
            'quantity' => 2
        ])->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customerOrderItem->getCustomerOrder());

        $this->processOrder->handle($crudOptions);

        $this->assertSame(0, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PENDING, $customerOrderItem->getCustomerOrder()->getStatus());
    }

    public function testHandleWithInactiveSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['isActive' => false])->_real();
        $customerOrderItem = CustomerOrderItemFactory::createOne(['product' => $supplierProduct->getProduct()])->_real();

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($customerOrderItem->getCustomerOrder());

        $this->processOrder->handle($crudOptions);

        $this->assertSame(0, $customerOrderItem->getPurchaseOrderItems()->count());
        $this->assertSame(OrderStatus::PENDING, $customerOrderItem->getCustomerOrder()->getStatus());
    }
}