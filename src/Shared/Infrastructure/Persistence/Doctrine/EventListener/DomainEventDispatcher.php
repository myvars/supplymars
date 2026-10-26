<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\EventListener;

use App\Shared\Domain\Event\AbstractDomainEvent;
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

        // Release every entity's events before dispatching any. A listener that flushes runs postFlush
        // again; with the events already released, that nested run has nothing left to dispatch, so a
        // flush of many entities doesn't nest one level deeper per entity.
        $events = [];
        foreach ($deletedEntities as $entity) {
            array_push($events, ...$entity->releaseDomainEvents());
        }

        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        foreach ($unitOfWork->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                // Removed since this flush began, so its row still exists: hold its events. A listener
                // that removes an entity here must flush; Doctrine drops a removal left unflushed.
                if ($entity instanceof DomainEventProviderInterface && !$unitOfWork->isScheduledForDelete($entity)) {
                    array_push($events, ...$entity->releaseDomainEvents());
                }
            }
        }

        $this->dispatchDomainEvents($events);
    }

    /**
     * @param list<AbstractDomainEvent> $events
     *
     * @throws ExceptionInterface
     */
    private function dispatchDomainEvents(array $events): void
    {
        foreach ($events as $event) {
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
