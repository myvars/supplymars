<?php

namespace App\Service\PurchaseOrder;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreatePurchaseOrderItem implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private CreatePurchaseOrder $purchaseOrderCreator,
        private DomainEventDispatcher $domainEventDispatcher,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $customerOrderItem = $crudOptions->getEntity();
        if (!$customerOrderItem instanceof CustomerOrderItem) {
            throw new \InvalidArgumentException('Entity must be instance of CustomerOrderItem');
        }

        $supplierProductId = $crudOptions->getCrudActionContext()['supplierProductId'] ?? null;
        if (null === $supplierProductId) {
            throw new \InvalidArgumentException('Supplier product ID is required');
        }

        $matchedSupplierProduct = null;
        foreach ($customerOrderItem->getProduct()->getSupplierProducts() as $supplierProduct) {
            if ($supplierProduct->getId() === $supplierProductId) {
                $matchedSupplierProduct = $supplierProduct;
                break;
            }
        }

        if (!$matchedSupplierProduct instanceof SupplierProduct) {
            throw new \InvalidArgumentException('Supplier product not found');
        }

        $this->fromOrderItem($customerOrderItem, $matchedSupplierProduct);
    }

    public function fromOrderItem(
        CustomerOrderItem $customerOrderItem,
        SupplierProduct $supplierProduct,
    ): PurchaseOrderItem {
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
            $customerOrder,
        ]);

        return $purchaseOrderItem;
    }

    private function getCustomerOrder(CustomerOrderItem $customerOrderItem): CustomerOrder
    {
        return $customerOrderItem->getCustomerOrder();
    }

    private function getEditablePurchaseOrder(
        CustomerOrder $customerOrder,
        Supplier $supplier,
    ): PurchaseOrder {
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
        SupplierProduct $supplierProduct,
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
        SupplierProduct $supplierProduct,
    ): PurchaseOrderItem {
        $purchaseOrderItem = PurchaseOrderItem::createFromCustomerOrderItem(
            $customerOrderItem,
            $purchaseOrder,
            $supplierProduct,
            $customerOrderItem->getOutstandingQty()
        );

        $errors = $this->validator->validate($purchaseOrderItem);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->persist($purchaseOrderItem);

        return $purchaseOrderItem;
    }
}
