<?php

namespace App\Purchasing\Application\Handler\PurchaseOrderItem;

use App\Purchasing\Application\Command\PurchaseOrderItem\UpdatePurchaseOrderItemStatus;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdatePurchaseOrderItemStatusHandler
{
    public function __construct(
        private PurchaseOrderItemRepository $purchaseOrderItems,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdatePurchaseOrderItemStatus $command): Result
    {
        $purchaseOrderItem = $this->purchaseOrderItems->getByPublicId($command->id);
        if (!$purchaseOrderItem instanceof PurchaseOrderItem) {
            return Result::fail('Purchase order item not found.');
        }

        try {
            $purchaseOrderItem->updateItemStatus(
                newStatus: $command->purchaseOrderStatus,
            );
        } catch (\Exception) {
            return Result::fail('Status cannot be updated.');
        }

        $errors = $this->validator->validate($purchaseOrderItem);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Purchase order item status updated.');
    }
}
