<?php

namespace App\Strategy;

use App\DTO\OrderCreateDto;
use App\Service\Crud\Core\CrudCreateStrategyInterface;
use App\Service\Order\OrderCreator;

final class CreateOrderStrategy implements CrudCreateStrategyInterface
{
    public function __construct(private readonly OrderCreator $orderCreator)
    {
    }

    public function create(object $entity, ?array $context): void
    {
        assert($entity instanceof OrderCreateDto);
        $this->orderCreator->createFromDto($entity);
    }
}