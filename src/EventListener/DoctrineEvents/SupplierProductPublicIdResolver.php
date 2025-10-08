<?php

namespace App\EventListener\DoctrineEvents;

use App\EventListener\AbstractPublicIdResolver;
use App\Repository\SupplierProductRepository;
use App\ValueObject\SupplierProductPublicId;

final readonly class SupplierProductPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(SupplierProductRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return SupplierProductPublicId::class;
    }
}
