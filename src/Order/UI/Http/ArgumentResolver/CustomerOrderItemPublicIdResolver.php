<?php

declare(strict_types=1);

namespace App\Order\UI\Http\ArgumentResolver;

use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderItemDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;

final readonly class CustomerOrderItemPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(CustomerOrderItemDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return OrderItemPublicId::class;
    }
}
