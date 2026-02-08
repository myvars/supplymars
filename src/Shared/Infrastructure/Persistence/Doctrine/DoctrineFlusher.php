<?php

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Application\FlusherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

final readonly class DoctrineFlusher implements FlusherInterface
{
    /**
     * Fields to ignore when detecting changes.
     * publicId has a Doctrine type mismatch (ulid type hydrates as object, property is string)
     * which causes spurious change detection.
     */
    private const array IGNORED_FIELDS = ['publicId'];

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
            || $this->hasMeaningfulUpdates($uow);

        $this->em->flush();

        return $hasChanges;
    }

    /**
     * Check if any entity has meaningful changes (ignoring spurious field changes).
     */
    private function hasMeaningfulUpdates(UnitOfWork $uow): bool
    {
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $changeSet = $uow->getEntityChangeSet($entity);
            $meaningfulChanges = array_diff_key($changeSet, array_flip(self::IGNORED_FIELDS));

            if ($meaningfulChanges !== []) {
                return true;
            }
        }

        return false;
    }
}
