<?php

namespace App\EventListener;

use App\Repository\CustomerOrderRepository;
use App\ValueObject\CustomerOrderPublicId;

final readonly class CustomerOrderPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(private CustomerOrderRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return CustomerOrderPublicId::class;
    }
}
