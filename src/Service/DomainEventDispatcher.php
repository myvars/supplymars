<?php
namespace App\Service;

use App\Entity\Interfaces\DomainEventProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DomainEventDispatcher
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $security
    ) {
    }

    /**
     * Dispatches one or more provider events.
     *
     * @param DomainEventProviderInterface|DomainEventProviderInterface[] $eventProviders
     */
    public function dispatchProviderEvents(DomainEventProviderInterface|array $eventProviders): void
    {
        if (!is_array($eventProviders)) {
            $eventProviders = [$eventProviders];
        }

        foreach ($eventProviders as $eventProvider) {
            if (!$eventProvider instanceof DomainEventProviderInterface) {
                throw new \InvalidArgumentException('Provider must implement DomainEventProviderInterface');
            }

            foreach ($eventProvider->releaseDomainEvents() as $event) {
                $event->setUser($this->security->getUser());
                $this->dispatchEvent($event);
            }
        }
    }

    public function dispatchEvent(Object $event): void
    {
        $this->eventDispatcher->dispatch($event);
    }
}