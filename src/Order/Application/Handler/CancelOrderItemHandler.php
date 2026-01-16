<?php

namespace App\Order\Application\Handler;

use App\Order\Application\Command\CancelOrderItem;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class CancelOrderItemHandler
{
    public function __construct(
        private OrderItemRepository $orderItems,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(CancelOrderItem $command): Result
    {
        $orderItem = $this->orderItems->getByPublicId($command->id);
        if (!$orderItem instanceof CustomerOrderItem) {
            return Result::fail('Order item not found.');
        }

        if (OrderStatus::CANCELLED === $orderItem->getStatus()) {
            return Result::fail('Order item already cancelled');
        }

        if (!$orderItem->allowCancel()) {
            return Result::fail('Order item cannot be cancelled');
        }

        $orderItem->cancelItem();
        $orderItem->getCustomerOrder()->generateStatus();

        $this->flusher->flush();

        return Result::ok('Order item cancelled.');
    }
}
