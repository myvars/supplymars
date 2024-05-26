<?php

namespace App\Strategy;

use App\DTO\OrderItemCreateDto;
use App\Service\Crud\Core\CrudCreateStrategyInterface;
use App\Service\Order\OrderItemCreator;

final class CreateOrderItemStrategy implements CrudCreateStrategyInterface
{
    public function __construct(private readonly OrderItemCreator $orderItemCreator)
    {
    }

    public function create(object $entity, ?array $context): void
    {
        assert($entity instanceof OrderItemCreateDto);
        $this->orderItemCreator->createFromDto($entity);
    }
}