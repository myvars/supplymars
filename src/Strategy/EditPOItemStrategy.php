<?php

namespace App\Strategy;

use App\DTO\PurchaseOrderItemEditDto;
use App\Service\Crud\Core\CrudUpdateStrategyInterface;
use App\Service\PurchaseOrder\PurchaseOrderItemUpdater;

final class EditPOItemStrategy implements CrudUpdateStrategyInterface
{
    public function __construct(private readonly PurchaseOrderItemUpdater $purchaseOrderItemUpdater)
    {
    }

    public function update(object $entity, ?array $context): void
    {
        assert($entity instanceof PurchaseOrderItemEditDto);
        $this->purchaseOrderItemUpdater->update($entity);
    }
}