<?php

namespace App\Order\Domain\Repository;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderId;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerOrderDoctrineRepository::class)]
interface OrderRepository extends FindByCriteriaInterface
{
    public function add(CustomerOrder $order): void;

    public function remove(CustomerOrder $order): void;

    public function get(OrderId $id): ?CustomerOrder;

    public function getByPublicId(OrderPublicId $publicId): ?CustomerOrder;

    /** @return CustomerOrder[]|null */
    public function findNextOrdersToBeProcessed(int $orderCount = 1): ?array;

    public function countOverdueOrders(): int;

    public function countPendingOrders(): int;
}
