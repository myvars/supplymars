<?php

namespace App\Service\PurchaseOrder;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreatePurchaseOrderItem
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private CreatePurchaseOrder $purchaseOrderCreator,
        private DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function fromOrder(CustomerOrderItem $customerOrderItem, SupplierProduct $supplierProduct): PurchaseOrderItem
    {
        if (!$customerOrderItem->allowEdit()) {
            throw new \InvalidArgumentException('Order item cannot be edited');
        }

        $customerOrder = $this->getCustomerOrder($customerOrderItem);
        $purchaseOrder = $this->getEditablePurchaseOrder($customerOrder, $supplierProduct->getSupplier());

        $purchaseOrderItem = $this->createPurchaseOrderItem($customerOrderItem, $purchaseOrder, $supplierProduct);

        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents([
            $purchaseOrderItem,
            $purchaseOrder,
            $customerOrderItem,
            $customerOrder
        ]);

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

        return $this->purchaseOrderCreator->fromOrder($customerOrder, $supplier);
    }

    private function createPurchaseOrderItem(
        CustomerOrderItem $customerOrderItem,
        PurchaseOrder $purchaseOrder,
        SupplierProduct $supplierProduct
    ): PurchaseOrderItem {
        $purchaseOrderItem = $this->getEditablePurchaseOrderItem($customerOrderItem, $purchaseOrder);
        if ($purchaseOrderItem instanceof PurchaseOrderItem) {
            $this->updateExistingPurchaseOrderItem($purchaseOrderItem);

            return $purchaseOrderItem;
        }

        return $this->createNewPurchaseOrderItem($customerOrderItem, $purchaseOrder, $supplierProduct);
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

    private function updateExistingPurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): void
    {
        $purchaseOrderItem->updateItem($purchaseOrderItem->getMaxQuantity());
        $purchaseOrderItem->getPurchaseOrder()->recalculateTotal();

        $this->entityManager->persist($purchaseOrderItem);
    }

    private function createNewPurchaseOrderItem(
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