<?php

declare(strict_types=1);

namespace App\Order\Application\Handler;

use App\Customer\Domain\Model\User\User;
use App\Customer\Infrastructure\Security\CurrentUserProvider;
use App\Order\Application\Command\LockOrder;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

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

        try {
            $user = $this->userProvider->get();
        } catch (\RuntimeException) {
            return Result::fail('User not found.');
        }

        // A toggle: it takes a free lock, and releases a held one, whoever holds it
        $order->lockOrder($order->getOrderLock() instanceof User ? null : $user, $user);

        $this->flusher->flush();

        return Result::ok();
    }
}
