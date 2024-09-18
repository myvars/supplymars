<?php

namespace App\Service\Order;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\CustomerOrder;
use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Enum\PurchaseOrderStatus;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;

final readonly class ProcessOrder
{
    public function __construct(
        private CreatePurchaseOrderItem $createPurchaseOrderItem,
        private ChangePurchaseOrderItemStatus $changePurchaseOrderItemStatus
    ) {
    }

    public function processOrder(CustomerOrder $customerOrder): void
    {
        foreach ($customerOrder->getCustomerOrderItems() as $customerOrderItem) {
            if ($customerOrderItem->getOutstandingQty() <= 0) {
                continue;
            }

            $lowestCostSupplier = $this->getLowestCostSupplierProduct(
                $customerOrderItem->getProduct(),
                $customerOrderItem->getQuantity()
            );

            if ($lowestCostSupplier instanceof SupplierProduct) {
                $this->createPurchaseOrderItem->fromOrder($customerOrderItem, $lowestCostSupplier);
            }
        }

        if ($this->checkAllOrderItemsProcessed($customerOrder)) {
            $this->changePurchaseOrderItemStatus($customerOrder, PurchaseOrderStatus::PROCESSING);
        }
    }

    private function getLowestCostSupplierProduct(Product $product, int $orderItemQty): ?SupplierProduct
    {
        $lowestCostSupplier = null;
        foreach ($product->getActiveSupplierProducts() as $supplierProduct) {
            if (
                $supplierProduct->getStock() >= $orderItemQty
                && (!isset($lowestCostSupplier) || $supplierProduct->getCost() < $lowestCostSupplier->getCost())
            ) {
                $lowestCostSupplier = $supplierProduct;
            }
        }

        return $lowestCostSupplier;
    }

    private function checkAllOrderItemsProcessed(CustomerOrder $customerOrder): bool
    {
        foreach ($customerOrder->getCustomerOrderItems() as $customerOrderItem) {
            if ($customerOrderItem->getOutstandingQty() > 0) {
                return false;
            }
        }

        return true;
    }

    private function changePurchaseOrderItemStatus(CustomerOrder $customerOrder, PurchaseOrderStatus $newStatus): void
    {
        foreach ($customerOrder->getPurchaseOrders() as $purchaseOrder) {
            foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
                $changePurchaseOrderItemStatusDto = new ChangePurchaseOrderItemStatusDto(
                    $purchaseOrderItem->getId(),
                    $newStatus
                );
                $this->changePurchaseOrderItemStatus->fromDto($changePurchaseOrderItemStatusDto);
            }
        }
    }
}