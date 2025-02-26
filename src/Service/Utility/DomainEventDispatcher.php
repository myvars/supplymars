<?php

namespace App\Service\Utility;

use App\Entity\DomainEventProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DomainEventDispatcher
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $security,
    ) {
    }

    /**
     * Dispatches one or more provider events.
     *
     * @param object|object[] $eventProviders
     */
    public function dispatchProviderEvents(object|array $eventProviders): void
    {
        if (!is_array($eventProviders)) {
            $eventProviders = [$eventProviders];
        }

        foreach ($eventProviders as $eventProvider) {
            if (!$eventProvider instanceof DomainEventProviderInterface) {
                continue;
            }

            foreach ($eventProvider->releaseDomainEvents() as $event) {
                $event->setUser($this->security->getUser());
                $this->dispatchEvent($event);
            }
        }
    }

    public function dispatchEvent(object $event): void
    {
        $this->eventDispatcher->dispatch($event);
    }
}
