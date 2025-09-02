<?php

namespace App\EventListener;

use App\ValueObject\AbstractUlidId;
use Doctrine\Persistence\ObjectRepository;

abstract readonly class AbstractPublicIdResolver implements PublicIdResolver
{
    public function __construct(private ObjectRepository $repository)
    {
    }

    public function resolve(AbstractUlidId $publicId): ?int
    {
        $entity = $this->repository->findOneBy(['publicId' => $publicId->value()]);

        return $entity?->getId();
    }
}
