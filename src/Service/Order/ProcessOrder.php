<?php

namespace App\Service\Order;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\CustomerOrder;
use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Enum\PurchaseOrderStatus;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;

final readonly class ProcessOrder implements CrudActionInterface
{
    public function __construct(
        private CreatePurchaseOrderItem $createPurchaseOrderItem,
        private ChangePurchaseOrderItemStatus $changePurchaseOrderItemStatus
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $customerOrder = $crudOptions->getEntity();
        if (!$customerOrder instanceof CustomerOrder) {
            throw new \InvalidArgumentException('Entity must be an instance of CustomerOrder');
        }

        $this->processOrder($customerOrder);
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
                $this->createPurchaseOrderItem->fromOrderItem($customerOrderItem, $lowestCostSupplier);
            }
        }

        if ($this->checkAllOrderItemsProcessed($customerOrder)) {
            $this->processPurchaseOrderItem($customerOrder);
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

    private function processPurchaseOrderItem(CustomerOrder $customerOrder): void
    {
        foreach ($customerOrder->getPurchaseOrders() as $purchaseOrder) {
            foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
                if ($purchaseOrderItem->getStatus() !== PurchaseOrderStatus::PENDING) {
                    continue;
                }

                $changePurchaseOrderItemStatusDto = new ChangePurchaseOrderItemStatusDto(
                    $purchaseOrderItem->getId(),
                    PurchaseOrderStatus::PROCESSING
                );
                $this->changePurchaseOrderItemStatus->fromDto($changePurchaseOrderItemStatusDto);
            }
        }
    }
}