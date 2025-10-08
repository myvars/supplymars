<?php

namespace App\Tests\Integration\Service\PurchaseOrder;

use App\DTO\EditPurchaseOrderItemDto;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\PurchaseOrderFactory;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\SupplierProductFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\PurchaseOrder\EditPurchaseOrderItem;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class EditPurchaseOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private EditPurchaseOrderItem $editPurchaseOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->editPurchaseOrderItem = new EditPurchaseOrderItem($entityManager);
        StaffUserStory::load();
    }

    public function testHandleWithValidEditPurchaseOrderItemDto(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['stock' => 100])->_real();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'quantity' => 10,
            'product' => $supplierProduct->getProduct()
        ]);
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'product' => $supplierProduct->getProduct(),
            'supplier' => $supplierProduct->getSupplier(),
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'quantity' => 3
        ]);

        $dto = new EditPurchaseOrderItemDto(
            $purchaseOrderItem->getId(),
            5
        );
        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->editPurchaseOrderItem->handle($crudOptions);

        $updatedPurchaseOrderItem = PurchaseOrderItemFactory::repository()->find($purchaseOrderItem->getId())->_real();
        $this->assertSame(5, $updatedPurchaseOrderItem->getQuantity());
    }

    public function testHandleWithZeroUpdateQuantity(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['stock' => 100])->_real();
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'quantity' => 10,
            'product' => $supplierProduct->getProduct()
        ])->_real();
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'product' => $supplierProduct->getProduct(),
            'supplier' => $supplierProduct->getSupplier(),
            'customerOrderItem' => $customerOrderItem,
            'supplierProduct' => $supplierProduct,
            'quantity' => 3
        ])->_real();
        $purchaseOrderItemId = $purchaseOrderItem->getId();
        $purchaseOrderId = $purchaseOrderItem->getPurchaseOrder()->getId();

        $dto = new EditPurchaseOrderItemDto(
            $purchaseOrderItem->getId(),
            0
        );
        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->editPurchaseOrderItem->handle($crudOptions);

        $this->assertNull(PurchaseOrderItemFactory::repository()->find($purchaseOrderItemId));
        $this->assertNull(PurchaseOrderFactory::repository()->find($purchaseOrderId));
    }
}
