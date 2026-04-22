<?php

declare(strict_types=1);

namespace App\Purchasing\UI\Http\ArgumentResolver;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;

final readonly class PurchaseOrderPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(PurchaseOrderDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return PurchaseOrderPublicId::class;
    }
}
