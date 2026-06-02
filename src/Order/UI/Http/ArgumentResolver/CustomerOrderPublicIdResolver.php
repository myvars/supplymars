<?php

declare(strict_types=1);

namespace App\Order\UI\Http\ArgumentResolver;

use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(index: OrderPublicId::class)]
final readonly class CustomerOrderPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(CustomerOrderDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }
}
