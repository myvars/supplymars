<?php

namespace App\Purchasing\Application\Service;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class EditablePurchaseOrderProvider
{
    public function __construct(
        private PurchaseOrderRepository $purchaseOrders,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Returns an editable purchase order for the supplier or creates a new one.
     * Throws \RuntimeException on validation failure.
     */
    public function getOrCreateForSupplier(CustomerOrder $order, Supplier $supplier): PurchaseOrder
    {
        foreach ($order->getPurchaseOrders() as $purchaseOrder) {
            if ($purchaseOrder->getSupplier() === $supplier && $purchaseOrder->allowEdit()) {
                return $purchaseOrder;
            }
        }

        $purchaseOrder = PurchaseOrder::createFromOrder(
            customerOrder: $order,
            supplier: $supplier
        );

        $errors = $this->validator->validate($purchaseOrder);
        if (count($errors) > 0) {
            throw new \RuntimeException('Cannot create purchase order');
        }

        $this->purchaseOrders->add($purchaseOrder);

        return $purchaseOrder;
    }
}
