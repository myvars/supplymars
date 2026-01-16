<?php

namespace App\Order\Application\Handler;

use App\Customer\Domain\Model\User\User;
use App\Order\Application\Command\LockOrder;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Infrastructure\Security\CurrentUserProvider;

final readonly class LockOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private FlusherInterface $flusher,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(LockOrder $command): Result
    {
        $order = $this->orders->getByPublicId($command->id);
        if (!$order instanceof CustomerOrder) {
            return Result::fail('Order not found.');
        }

        if (!$order->getOrderLock() instanceof User) {
            try {
                $user = $this->userProvider->get();
            } catch (\RuntimeException) {
                return Result::fail('User not found.');
            }

            $order->lockOrder($user);
        } else {
            $order->lockOrder(null);
        }

        $this->flusher->flush();

        return Result::ok();
    }
}
