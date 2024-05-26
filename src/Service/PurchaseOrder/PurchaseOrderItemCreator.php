<?php

namespace App\Service\PurchaseOrder;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PurchaseOrderItemCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly PurchaseOrderCreator $purchaseOrderCreator
    ) {
    }

    public function create(
        CustomerOrderItem $customerOrderItem,
        SupplierProduct $supplierProduct,
        bool $flush = true
    ): PurchaseOrderItem {
        $customerOrder = $this->getCustomerOrder($customerOrderItem);
        $purchaseOrder = $this->getEditablePurchaseOrder($customerOrder, $supplierProduct->getSupplier());

        if ($purchaseOrderItem = $this->getEditablePurchaseOrderItem($customerOrderItem, $purchaseOrder)) {
            $this->updatePurchaseOrderItem($purchaseOrderItem);
        } else {
            $purchaseOrderItem = $this->createPurchaseOrderItem($customerOrderItem, $purchaseOrder, $supplierProduct);
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        return $purchaseOrderItem;
    }

    private function getCustomerOrder(CustomerOrderItem $customerOrderItem): CustomerOrder
    {
        return $customerOrderItem->getCustomerOrder();
    }

    private function getEditablePurchaseOrder(CustomerOrder $customerOrder, Supplier $supplier): PurchaseOrder
    {
        foreach ($customerOrder->getPurchaseOrders() as $purchaseOrder) {
            if ($purchaseOrder->getSupplier() === $supplier && $purchaseOrder->allowEdit()) {
                return $purchaseOrder;
            }
        }

        return $this->purchaseOrderCreator->create($customerOrder, $supplier);
    }

    private function getEditablePurchaseOrderItem(
        CustomerOrderItem $customerOrderItem,
        PurchaseOrder $purchaseOrder,
    ): ?PurchaseOrderItem {
        foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
            if ($purchaseOrderItem->getCustomerOrderItem() === $customerOrderItem) {
                return $purchaseOrderItem;
            }
        }

        return null;
    }

    private function updatePurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): void
    {
        $purchaseOrderItem->updateItem($purchaseOrderItem->getMaxQuantity());
        $purchaseOrderItem->getPurchaseOrder()->recalculateTotal();
        $this->entityManager->persist($purchaseOrderItem);
    }

    private function createPurchaseOrderItem(
        CustomerOrderItem $customerOrderItem,
        PurchaseOrder $purchaseOrder,
        SupplierProduct $supplierProduct
    ): PurchaseOrderItem {
        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            $customerOrderItem,
            $purchaseOrder,
            $supplierProduct,
            $customerOrderItem->getOutstandingQty()
        );

        $errors = $this->validator->validate($purchaseOrderItem);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }

        $purchaseOrder->addPurchaseOrderItem($purchaseOrderItem);
        $customerOrderItem->addPurchaseOrderItem($purchaseOrderItem);

        $this->entityManager->persist($customerOrderItem);
        $this->entityManager->persist($purchaseOrderItem);

        return $purchaseOrderItem;
    }
}