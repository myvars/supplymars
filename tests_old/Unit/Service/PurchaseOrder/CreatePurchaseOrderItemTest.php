<?php

namespace App\Tests\Unit\Service\PurchaseOrder;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierProductDoctrineRepository;
use App\Service\Crud\Common\CrudContext;
use App\Service\PurchaseOrder\CreatePurchaseOrder;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreatePurchaseOrderItemTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private MockObject $createPurchaseOrder;

    private CreatePurchaseOrderItem $createPurchaseOrderItem;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->createPurchaseOrder = $this->createMock(CreatePurchaseOrder::class);
        $this->createPurchaseOrderItem = new CreatePurchaseOrderItem(
            $this->em,
            $this->validator,
            $this->createPurchaseOrder
        );
    }

    public function testHandleWithNonCustomerOrderItemEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be instance of CustomerOrderItem');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->createPurchaseOrderItem)($context);
    }

    public function testHandleWithMissingSupplierProductId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier product ID is required');

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrderItem);
        $context->method('getCrudHandlerContext')->willReturn([]);

        ($this->createPurchaseOrderItem)($context);
    }

    public function testHandleWithUnmatchedSupplierProduct(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier product not found');

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrderItem);
        $context->method('getCrudHandlerContext')->willReturn(['supplierProductId' => 1]);

        ($this->createPurchaseOrderItem)($context);
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

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrderItem);
        $context->method('getCrudHandlerContext')->willReturn(['supplierProductId' => 1]);

        ($this->createPurchaseOrderItem)($context);
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
        $this->em->method('getRepository')->willReturnMap([
            [CustomerOrder::class, $this->createMock(CustomerOrderDoctrineRepository::class)],
            [SupplierProduct::class, $this->createMock(SupplierProductDoctrineRepository::class)],
        ]);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->createPurchaseOrderItem->fromOrderItem($customerOrderItem, $supplierProduct);
    }
}
