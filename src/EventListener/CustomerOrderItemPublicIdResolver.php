<?php

namespace App\EventListener;

use App\Repository\CustomerOrderItemRepository;
use App\ValueObject\CustomerOrderItemPublicId;

final readonly class CustomerOrderItemPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(CustomerOrderItemRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return CustomerOrderItemPublicId::class;
    }
}
