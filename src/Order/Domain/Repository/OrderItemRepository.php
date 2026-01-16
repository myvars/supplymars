<?php

namespace App\Order\Domain\Repository;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderItemId;
use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderItemDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CustomerOrderItemDoctrineRepository::class)]
interface OrderItemRepository
{
    public function add(CustomerOrderItem $orderItem): void;

    public function remove(CustomerOrderItem $orderItem): void;

    public function get(OrderItemId $id): ?CustomerOrderItem;

    public function getByPublicId(OrderItemPublicId $publicId): ?CustomerOrderItem;
}
