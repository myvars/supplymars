<?php

namespace App\Tests\Integration\Service\PurchaseOrder;

use App\Purchasing\Application\DTO\EditPurchaseOrderItemDto;
use App\Service\Crud\Common\CrudContext;
use App\Service\PurchaseOrder\EditPurchaseOrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\PurchaseOrderFactory;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use tests\Shared\Factory\SupplierProductFactory;
use Zenstruck\Foundry\Test\Factories;

class EditPurchaseOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private EditPurchaseOrderItem $editPurchaseOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->editPurchaseOrderItem = new EditPurchaseOrderItem($em);
        StaffUserStory::load();
    }

    public function testHandleWithValidEditPurchaseOrderItemDto(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['stock' => 100]);
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'quantity' => 10,
            'product' => $supplierProduct->getProduct(),
        ]);
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'product' => $supplierProduct->getProduct(),
            'supplier' => $supplierProduct->getSupplier(),
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'quantity' => 3,
        ]);

        $dto = new EditPurchaseOrderItemDto(
            $purchaseOrderItem->getId(),
            5
        );
        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->editPurchaseOrderItem)($context);

        $updatedPurchaseOrderItem = PurchaseOrderItemFactory::repository()->find($purchaseOrderItem->getId());
        $this->assertSame(5, $updatedPurchaseOrderItem->getQuantity());
    }

    public function testHandleWithZeroUpdateQuantity(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['stock' => 100]);
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'quantity' => 10,
            'product' => $supplierProduct->getProduct(),
        ]);
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'product' => $supplierProduct->getProduct(),
            'supplier' => $supplierProduct->getSupplier(),
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'quantity' => 3,
        ]);
        $purchaseOrderItemId = $purchaseOrderItem->getId();
        $purchaseOrderId = $purchaseOrderItem->getPurchaseOrder()->getId();

        $dto = new EditPurchaseOrderItemDto(
            $purchaseOrderItem->getId(),
            0
        );
        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->editPurchaseOrderItem)($context);

        $this->assertNull(PurchaseOrderItemFactory::repository()->find($purchaseOrderItemId));
        $this->assertNull(PurchaseOrderFactory::repository()->find($purchaseOrderId));
    }
}
