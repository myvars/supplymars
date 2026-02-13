<?php

namespace App\Purchasing\Application\Service;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use Psr\Log\LoggerInterface;

final readonly class OrderAllocator
{
    public function __construct(
        private EditablePurchaseOrderProvider $purchaseOrderProvider,
        private OrderItemAllocator $orderItemAllocator,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Allocates outstanding customer order items to purchase orders.
     * Continues on per-item failures to maximize progress.
     */
    public function process(CustomerOrder $order): void
    {
        foreach ($order->getCustomerOrderItems() as $orderItem) {
            $outstanding = $orderItem->getOutstandingQty();
            if ($outstanding <= 0) {
                continue;
            }

            $bestSource = $orderItem->getProduct()->getBestSourceWithMinQuantity($outstanding);
            if (!$bestSource instanceof SupplierProduct) {
                continue;
            }

            try {
                $purchaseOrder = $this->purchaseOrderProvider->getOrCreateForSupplier(
                    $orderItem->getCustomerOrder(),
                    $bestSource->getSupplier()
                );

                $this->orderItemAllocator->forOrderItem(
                    $purchaseOrder,
                    $orderItem,
                    $bestSource
                );
            } catch (\Throwable $throwable) {
                $this->logger->warning('Failed to allocate order item {id}', [
                    'id' => $orderItem->getId(),
                    'error' => $throwable->getMessage(),
                ]);
                continue;
            }
        }

        if ($this->allItemsAllocated($order)) {
            $this->updateItemStatus($order);
        }
    }

    /**
     * Returns true when no order item has outstanding quantity.
     */
    public function allItemsAllocated(CustomerOrder $order): bool
    {
        foreach ($order->getCustomerOrderItems() as $orderItem) {
            if ($orderItem->getOutstandingQty() > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Moves purchase order items from PENDING to PROCESSING for this order.
     */
    private function updateItemStatus(CustomerOrder $order): void
    {
        foreach ($order->getPurchaseOrders() as $purchaseOrder) {
            foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
                if (PurchaseOrderStatus::PENDING !== $purchaseOrderItem->getStatus()) {
                    continue;
                }

                $purchaseOrderItem->updateItemStatus(
                    newStatus: PurchaseOrderStatus::PROCESSING,
                );
            }
        }
    }
}
