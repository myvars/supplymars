<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Handler\PurchaseOrderItem;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Application\Command\PurchaseOrderItem\UpdatePurchaseOrderItemQuantity;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdatePurchaseOrderItemQuantityHandler
{
    private const string ROUTE = 'app_order_show';

    public function __construct(
        private PurchaseOrderItemRepository $purchaseOrderItems,
        private PurchaseOrderRepository $purchaseOrders,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdatePurchaseOrderItemQuantity $command): Result
    {
        $purchaseOrderItem = $this->purchaseOrderItems->getByPublicId($command->id);
        if (!$purchaseOrderItem instanceof PurchaseOrderItem) {
            return Result::fail('Purchase order item not found.');
        }

        if ($command->quantity === 0) {
            return $this->handleRemove($purchaseOrderItem);
        }

        return $this->handleUpdate($purchaseOrderItem, $command);
    }

    private function handleRemove(PurchaseOrderItem $purchaseOrderItem): Result
    {
        $customerOrderItem = $purchaseOrderItem->getCustomerOrderItem();
        if (!$customerOrderItem instanceof CustomerOrderItem) {
            return Result::fail('Customer order item not found.');
        }

        $purchaseOrder = $purchaseOrderItem->getPurchaseOrder();
        $customerOrderItem->removePurchaseOrderItem($purchaseOrderItem);
        $purchaseOrder->removePurchaseOrderItem($purchaseOrderItem);
        $this->purchaseOrderItems->remove($purchaseOrderItem);

        $redirect = null;
        if ($purchaseOrder->getPurchaseOrderItems()->isEmpty()) {
            $this->purchaseOrders->remove($purchaseOrder);
            $redirect = new RedirectTarget(
                route: self::ROUTE,
                params: ['id' => $customerOrderItem->getCustomerOrder()->getPublicId()],
            );
        }

        $this->flusher->flush();

        return Result::ok(
            message: 'Purchase order item removed',
            redirect: $redirect,
        );
    }

    private function handleUpdate(
        PurchaseOrderItem $purchaseOrderItem,
        UpdatePurchaseOrderItemQuantity $command,
    ): Result {
        try {
            $purchaseOrderItem->updateItemQuantity(
                quantity: $command->quantity
            );
            $purchaseOrderItem->getPurchaseOrder()->recalculateTotal();
        } catch (\LogicException|\InvalidArgumentException $e) {
            return Result::fail($e->getMessage());
        }

        $errors = $this->validator->validate($purchaseOrderItem);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Order item updated');
    }
}
