<?php

namespace App\Shared\Application\Identity;

use App\Shared\Domain\ValueObject\AbstractUlidId;
use Doctrine\Persistence\ObjectRepository;

abstract readonly class AbstractPublicIdResolver implements PublicIdResolverInterface
{
    /**
     * @param ObjectRepository<object> $repository
     */
    public function __construct(private ObjectRepository $repository)
    {
    }

    public function resolve(AbstractUlidId $publicId): ?int
    {
        $entity = $this->repository->findOneBy(['publicId' => $publicId->value()]);

        // @phpstan-ignore method.notFound (entities always have getId())
        return $entity?->getId();
    }
}
