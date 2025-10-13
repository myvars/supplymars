<?php

namespace App\Purchasing\UI\Http\ArgumentResolver;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderItemDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;

final readonly class PurchaseOrderItemPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(PurchaseOrderItemDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return PurchaseOrderItemPublicId::class;
    }
}
