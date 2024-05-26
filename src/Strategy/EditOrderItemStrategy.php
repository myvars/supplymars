<?php

namespace App\Strategy;

use App\DTO\OrderItemEditDto;
use App\Service\Crud\Core\CrudUpdateStrategyInterface;
use App\Service\Order\OrderItemUpdater;

final class EditOrderItemStrategy implements CrudUpdateStrategyInterface
{
    public function __construct(private readonly OrderItemUpdater $orderItemUpdater)
    {
    }

    public function update(object $entity, ?array $context): void
    {
        assert($entity instanceof OrderItemEditDto);
        $this->orderItemUpdater->updateFromDto($entity);
    }
}