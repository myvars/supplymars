<?php

declare(strict_types=1);

namespace App\Order\Application\Handler;

use App\Order\Application\Command\CancelOrder;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(CancelOrder $command): Result
    {
        $order = $this->orders->getByPublicId($command->id);
        if (!$order instanceof CustomerOrder) {
            return Result::fail('Order not found.');
        }

        try {
            $order->cancelOrder();
        } catch (\LogicException $logicException) {
            return Result::fail($logicException->getMessage());
        }

        $this->flusher->flush();

        return Result::ok('Order cancelled.');
    }
}
