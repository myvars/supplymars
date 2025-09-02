<?php

namespace App\Tests\Unit\Service\Utility;

use App\Entity\DomainEventProviderInterface;
use App\Event\AbstractDomainEvent;
use App\Service\Utility\DomainEventDispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DomainEventDispatcherTest extends TestCase
{
    private EventDispatcherInterface $eventDispatcher;
    private Security $security;
    private DomainEventDispatcher $domainEventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->domainEventDispatcher = new DomainEventDispatcher($this->eventDispatcher, $this->security);
    }

    public function testDispatchProviderEventsWithoutDomainEventProvider(): void
    {
        $eventProvider = new \stdClass();

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->domainEventDispatcher->dispatchProviderEvents($eventProvider);
    }

    public function testDispatchProviderEventsWithDomainEventProvider(): void
    {
        $event = $this->createMock(AbstractDomainEvent::class);
        $eventProvider = $this->createMock(DomainEventProviderInterface::class);
        $eventProvider->method('releaseDomainEvents')->willReturn([$event]);

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with($event);

        $this->domainEventDispatcher->dispatchProviderEvents($eventProvider);
    }

    public function testDispatchEvent(): void
    {
        $event = new \stdClass();

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with($event);

        $this->domainEventDispatcher->dispatchEvent($event);
    }
}
