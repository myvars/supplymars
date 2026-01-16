<?php

namespace App\Order\Application\Handler;

use App\Order\Application\Command\UpdateOrderItem;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdateOrderItemHandler
{
    public function __construct(
        private OrderItemRepository $orderItems,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
        private MarkupCalculator $markupCalculator,
    ) {
    }

    public function __invoke(UpdateOrderItem $command): Result
    {
        $orderItem = $this->orderItems->getByPublicId($command->orderItemId);
        if (!$orderItem instanceof CustomerOrderItem) {
            return Result::fail('Order item not found.');
        }

        if ($command->quantity < 0) {
            return Result::fail('Quantity must be >= 0');
        }

        if ($command->quantity === 0) {
            return $this->handleRemove($orderItem);
        }

        return $this->handleUpdate($orderItem, $command);
    }

    private function handleRemove(CustomerOrderItem $orderItem): Result
    {
        if ($orderItem->getQtyAddedToPurchaseOrders() > 0) {
            return Result::fail('Allocated PO qty > 0');
        }

        if (!$orderItem->getCustomerOrder()->allowEdit()) {
            return Result::fail('Order not editable');
        }

        $orderItem->getCustomerOrder()->removeCustomerOrderItem($orderItem);
        $this->orderItems->remove($orderItem);
        $this->flusher->flush();

        return Result::ok('Order item removed');
    }

    private function handleUpdate(CustomerOrderItem $orderItem, UpdateOrderItem $command): Result
    {
        $priceBeforeVat = $this->markupCalculator->calculateSellPriceBeforeVat(
            $command->priceIncVat,
            $orderItem->getVatRate()->getRate()
        );

        try {
            $orderItem->updateItem(
                quantity: $command->quantity,
                price: $priceBeforeVat,
                priceIncVat: $command->priceIncVat,
                weight: $orderItem->getWeight(),
            );
        } catch (\LogicException) {
            return Result::fail('Below allocated qty');
        }

        $errors = $this->validator->validate($orderItem);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Order item updated');
    }
}
