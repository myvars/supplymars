<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Application\FlusherInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineFlusher implements FlusherInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function flush(): bool
    {
        $uow = $this->em->getUnitOfWork();

        // Compute changes before checking (handles deferred change detection)
        $uow->computeChangeSets();

        $hasChanges = $uow->getScheduledEntityInsertions() !== []
            || $uow->getScheduledEntityDeletions() !== []
            || $uow->getScheduledCollectionDeletions() !== []
            || $uow->getScheduledCollectionUpdates() !== []
            || $uow->getScheduledEntityUpdates() !== [];

        $this->em->flush();

        return $hasChanges;
    }
}
