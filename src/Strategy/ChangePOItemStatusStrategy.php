<?php

namespace App\Strategy;

use App\DTO\PurchaseOrderItemStatusChangeDto;
use App\Service\Crud\Core\CrudUpdateStrategyInterface;
use App\Service\PurchaseOrder\PurchaseOrderItemStatusUpdater;

final class ChangePOItemStatusStrategy implements CrudUpdateStrategyInterface
{
    public function __construct(private readonly PurchaseOrderItemStatusUpdater $purchaseOrderItemStatusUpdater)
    {
    }

    public function update(object $entity, ?array $context): void
    {
        assert($entity instanceof PurchaseOrderItemStatusChangeDto);
        $this->purchaseOrderItemStatusUpdater->update($entity);
    }
}