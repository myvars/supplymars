<?php

namespace App\EventListener\DoctrineEvents;

use App\Entity\DomainEventProviderInterface;
use App\Event\AsyncDomainEventInterface;
use App\Event\DomainEventInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postFlush)]
final readonly class DomainEventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $identityMap = $unitOfWork->getIdentityMap();

        if (empty($identityMap)) {
            return;
        }

        foreach ($identityMap as $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof DomainEventProviderInterface) {
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
