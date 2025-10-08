<?php

namespace App\Tests\Unit\Service\PurchaseOrder;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Repository\CustomerOrderRepository;
use App\Repository\SupplierProductRepository;
use App\Service\Crud\Common\CrudOptions;
use App\Service\PurchaseOrder\CreatePurchaseOrder;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreatePurchaseOrderItemTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $validator;

    private MockObject $createPurchaseOrder;

    private CreatePurchaseOrderItem $createPurchaseOrderItem;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->createPurchaseOrder = $this->createMock(CreatePurchaseOrder::class);
        $this->createPurchaseOrderItem = new CreatePurchaseOrderItem(
            $this->entityManager,
            $this->validator,
            $this->createPurchaseOrder
        );
    }

    public function testHandleWithNonCustomerOrderItemEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be instance of CustomerOrderItem');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->createPurchaseOrderItem->handle($crudOptions);
    }

    public function testHandleWithMissingSupplierProductId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier product ID is required');

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrderItem);
        $crudOptions->method('getCrudActionContext')->willReturn([]);

        $this->createPurchaseOrderItem->handle($crudOptions);
    }

    public function testHandleWithUnmatchedSupplierProduct(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier product not found');

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrderItem);
        $crudOptions->method('getCrudActionContext')->willReturn(['supplierProductId' => 1]);

        $this->createPurchaseOrderItem->handle($crudOptions);
    }

    public function testHandleWithoutAllowEdit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order item cannot be edited');

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getId')->willReturn(1);

        $product = $this->createMock(Product::class);
        $product->method('getSupplierProducts')->willReturn(new ArrayCollection([$supplierProduct]));

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrderItem->method('getProduct')->willReturn($product);
        $customerOrderItem->method('allowEdit')->willReturn(false);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrderItem);
        $crudOptions->method('getCrudActionContext')->willReturn(['supplierProductId' => 1]);

        $this->createPurchaseOrderItem->handle($crudOptions);
    }

    public function testFromOrderItemSuccessfully(): void
    {
        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $customerOrder = $this->createMock(CustomerOrder::class);
        $purchaseOrder = $this->createMock(PurchaseOrder::class);

        $customerOrderItem->method('allowEdit')->willReturn(true);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);
        $customerOrderItem->method('getOutstandingQty')->willReturn(1);
        $supplierProduct->method('getSupplier')->willReturn($this->createMock(Supplier::class));
        $supplierProduct->method('getCost')->willReturn('10.00');
        $supplierProduct->method('getWeight')->willReturn(500);


        $this->createPurchaseOrder->method('fromOrder')->willReturn($purchaseOrder);
        $this->entityManager->method('getRepository')->willReturnMap([
            [CustomerOrder::class, $this->createMock(CustomerOrderRepository::class)],
            [SupplierProduct::class, $this->createMock(SupplierProductRepository::class)]
        ]);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->createPurchaseOrderItem->fromOrderItem($customerOrderItem, $supplierProduct);
    }
}
