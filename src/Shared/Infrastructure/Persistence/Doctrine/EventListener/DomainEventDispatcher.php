<?php

namespace App\Shared\Infrastructure\Persistence\Doctrine\EventListener;

use App\Shared\Domain\Event\AsyncDomainEventInterface;
use App\Shared\Domain\Event\DomainEventInterface;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class DomainEventDispatcher
{
    /**
     * Entities deleted by the current flush. The flush takes them out of the identity map,
     * so postFlush cannot find them there.
     *
     * @var list<DomainEventProviderInterface>
     */
    private array $deletedEntities = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->deletedEntities = [];

        foreach ($args->getObjectManager()->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof DomainEventProviderInterface) {
                $this->deletedEntities[] = $entity;
            }
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        // A listener may flush again, which runs onFlush and replaces the list.
        $deletedEntities = $this->deletedEntities;
        $this->deletedEntities = [];

        foreach ($deletedEntities as $entity) {
            $this->dispatchDomainEvents($entity);
        }

        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        foreach ($unitOfWork->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                // Removed since this flush began: its events wait for the flush that deletes it.
                if ($entity instanceof DomainEventProviderInterface && !$unitOfWork->isScheduledForDelete($entity)) {
                    $this->dispatchDomainEvents($entity);
                }
            }
        }
    }

    /**
     * @throws ExceptionInterface
     */
    private function dispatchDomainEvents(DomainEventProviderInterface $entity): void
    {
        foreach ($entity->releaseDomainEvents() as $event) {
            if (!$event instanceof DomainEventInterface) {
                continue;
            }

            $this->eventDispatcher->dispatch($event, $event::class);

            if ($event instanceof AsyncDomainEventInterface) {
                $this->messageBus->dispatch($event);
            }
        }
    }
}
