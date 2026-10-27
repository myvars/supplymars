<?php

declare(strict_types=1);

namespace App\Order\Application\Handler;

use App\Order\Application\Command\AllocateOrder;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Purchasing\Application\Service\OrderAllocator;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class AllocateOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private OrderAllocator $orderAllocator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(AllocateOrder $command): Result
    {
        $order = $this->orders->getByPublicId($command->id);
        if (!$order instanceof CustomerOrder) {
            return Result::fail('Order not found.');
        }

        // Nothing outstanding still sends PENDING PO items (e.g. allocated by hand) to their suppliers
        if ($this->orderAllocator->allItemsAllocated($order) && !$this->orderAllocator->hasPendingItems($order)) {
            return Result::fail('No items to allocate.');
        }

        $this->orderAllocator->process($order);

        $this->flusher->flush();

        if (!$this->orderAllocator->allItemsAllocated($order)) {
            return Result::fail('Cannot allocate all items');
        }

        return Result::ok('Order processed.');
    }
}
