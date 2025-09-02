<?php

namespace App\EventListener;

use App\Repository\PurchaseOrderRepository;
use App\ValueObject\PurchaseOrderPublicId;

final readonly class PurchaseOrderPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(private PurchaseOrderRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return PurchaseOrderPublicId::class;
    }
}
