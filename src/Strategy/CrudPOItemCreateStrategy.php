<?php

namespace App\Strategy;

use App\DTO\OrderItemCreateDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use App\Entity\SupplierProduct;
use App\Service\Crud\Core\CrudCreateStrategyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsAlias('app.crud.po.item.create.strategy')]
final class CrudPOItemCreateStrategy implements CrudCreateStrategyInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function create(object $entity, ?array $context): void
    {
        assert($entity instanceof CustomerOrderItem);

        $customerOrder = $entity->getCustomerOrder();
        assert($customerOrder instanceof CustomerOrder);

        $supplierProduct = $this->getSupplierProduct($entity->getProduct(), $context['supplierProductId']);
        assert($supplierProduct instanceof SupplierProduct);

        $purchaseOrder = $this->getEditablePurchaseOrder($customerOrder, $supplierProduct->getSupplier()) ?:
            $this->createPurchaseOrder($customerOrder, $supplierProduct->getSupplier());

        if ($purchaseOrderItem = $this->getEditablePurchaseOrderItem($purchaseOrder, $entity)) {
            $this->updatePurchaseOrderItem($purchaseOrderItem);
            $this->entityManager->flush();

            return;
        }

        $this->createPurchaseOrderItem($entity, $purchaseOrder, $supplierProduct);
        $this->entityManager->flush();
    }

    private function getSupplierProduct(
        Product $product,
        int $supplierProductId
    ): ?SupplierProduct {
        foreach($product->getSupplierProducts() as $supplierProduct) {
            if ($supplierProduct->getId() === $supplierProductId) {
                return $supplierProduct;
            }
        }

        return null;
    }

    private function getEditablePurchaseOrder(
        CustomerOrder $customerOrder,
        Supplier $supplier
    ): ?PurchaseOrder {
        foreach ($customerOrder->getPurchaseOrders() as $purchaseOrder) {
            if ($supplier === $purchaseOrder->getSupplier() && $purchaseOrder->allowEdit()) {
                return $purchaseOrder;
            }
        }

        return null;
    }

    private function getEditablePurchaseOrderItem(
        PurchaseOrder $purchaseOrder,
        CustomerOrderItem $customerOrderItem
    ): ?PurchaseOrderItem {
        foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
            if ($customerOrderItem === $purchaseOrderItem->getCustomerOrderItem()) {
                return $purchaseOrderItem;
            }
        }

        return null;
    }

    private function createPurchaseOrder(
        CustomerOrder $customerOrder,
        Supplier $supplier
    ): PurchaseOrder {
        $purchaseOrder = PurchaseOrder::createFromCustomerOrder($customerOrder, $supplier);
        $errors = $this->validator->validate($purchaseOrder);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }
        $this->entityManager->persist($purchaseOrder);

        return $purchaseOrder;
    }

    public function updatePurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): void
    {
        $purchaseOrderItem->updateItem($purchaseOrderItem->getMaxQuantity());
        $purchaseOrderItem->getPurchaseOrder()->recalculateTotal();
        $this->entityManager->persist($purchaseOrderItem);
    }

    public function createPurchaseOrderItem(
        CustomerOrderItem $customerOrderItem,
        PurchaseOrder $purchaseOrder,
        SupplierProduct $supplierProduct
    ): void {
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
    }
}