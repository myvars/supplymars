<?php

namespace App\Purchasing\Application\Service;


use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class OrderItemAllocator
{
    public function __construct(
        private PurchaseOrderItemRepository $purchaseOrderItems,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Allocates a purchase order item for the given customer order item.
     * Behavior:
     * - If an existing item is found, its quantity is increased to the maximum and the PO total recalculated.
     * - If none exists, a new item is created for the outstanding quantity.
     */
    public function forOrderItem(
        PurchaseOrder $purchaseOrder,
        CustomerOrderItem $orderItem,
        SupplierProduct $supplierProduct,
    ): PurchaseOrderItem {
        // Try existing first
        foreach ($purchaseOrder->getPurchaseOrderItems() as $poItem) {
            if ($poItem->getCustomerOrderItem() === $orderItem) {
                $poItem->updateItemQuantity(
                    quantity: $poItem->getMaxQuantity()
                );

                return $poItem;
            }
        }

        if ($orderItem->getOutstandingQty() <= 0) {
            throw new \RuntimeException('No quantity to allocate');
        }

        $poItem = PurchaseOrderItem::createFromCustomerOrderItem(
            customerOrderItem: $orderItem,
            purchaseOrder: $purchaseOrder,
            supplierProduct: $supplierProduct,
            quantity: $orderItem->getOutstandingQty()
        );

        $errors = $this->validator->validate($poItem);
        if (count($errors) > 0) {
            throw new \RuntimeException((string) $errors);
        }

        $this->purchaseOrderItems->add($poItem);
        $purchaseOrder->recalculateTotal();

        return $poItem;
    }
}
