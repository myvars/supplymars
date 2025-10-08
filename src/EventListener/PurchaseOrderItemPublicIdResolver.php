<?php

namespace App\EventListener;

use App\Repository\PurchaseOrderItemRepository;
use App\ValueObject\PurchaseOrderItemPublicId;

final readonly class PurchaseOrderItemPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(PurchaseOrderItemRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return PurchaseOrderItemPublicId::class;
    }
}
