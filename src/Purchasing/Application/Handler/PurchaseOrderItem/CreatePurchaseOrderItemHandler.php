<?php

namespace App\Purchasing\Application\Handler\PurchaseOrderItem;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Purchasing\Application\Command\PurchaseOrderItem\CreatePurchaseOrderItem;
use App\Purchasing\Application\Service\EditablePurchaseOrderProvider;
use App\Purchasing\Application\Service\OrderItemAllocator;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class CreatePurchaseOrderItemHandler
{
    public function __construct(
        private OrderItemRepository $orderItems,
        private SupplierProductRepository $supplierProducts,
        private EditablePurchaseOrderProvider $purchaseOrderProvider,
        private OrderItemAllocator $orderItemAllocator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(CreatePurchaseOrderItem $command): Result
    {
        $orderItem = $this->orderItems->getByPublicId($command->id);
        if (!$orderItem instanceof CustomerOrderItem) {
            return Result::fail('Order item not found.');
        }

        if (!$orderItem->allowEdit()) {
            return Result::fail('Order item cannot be edited');
        }

        $supplierProduct = $this->supplierProducts->getByPublicId($command->supplierProductId);
        if (!$supplierProduct instanceof SupplierProduct) {
            return Result::fail('Supplier product not found');
        }

        if (!$orderItem->getProduct()->getSupplierProducts()->contains($supplierProduct)) {
            return Result::fail('Supplier product source missing');
        }

        try {
            $purchaseOrder = $this->purchaseOrderProvider->getOrCreateForSupplier(
                $orderItem->getCustomerOrder(),
                $supplierProduct->getSupplier()
            );
            $purchaseOrderItem = $this->orderItemAllocator->forOrderItem(
                $purchaseOrder,
                $orderItem,
                $supplierProduct,
            );
        } catch (\Exception $e) {
            return Result::fail('Cannot edit purchase order item');
        }

        $this->flusher->flush();

        return Result::ok(
            'Purchase order item updated',
            PurchaseOrderItemId::fromInt($purchaseOrderItem->getId())
        );
    }
}
