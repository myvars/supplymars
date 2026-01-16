<?php

namespace App\Purchasing\UI\Http\ArgumentResolver;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierProductDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;

final readonly class SupplierProductPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(SupplierProductDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return SupplierProductPublicId::class;
    }
}
