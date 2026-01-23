<?php

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine\EventListener;

use App\Shared\Domain\Event\AsyncDomainEventInterface;
use App\Shared\Domain\Event\DomainEventInterface;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\EventListener\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DomainEventDispatcherTest extends TestCase
{
    private MockObject $eventDispatcher;

    private MockObject $messageBus;

    private DomainEventDispatcher $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->listener = new DomainEventDispatcher($this->eventDispatcher, $this->messageBus);
    }

    public function testPostFlushDoesNothingWhenIdentityMapEmpty(): void
    {
        $args = $this->makeArgs(identityMap: []);

        $this->eventDispatcher->expects(self::never())->method('dispatch');
        $this->messageBus->expects(self::never())->method('dispatch');

        $this->listener->postFlush($args);
    }

    public function testPostFlushIgnoresEntitiesWithoutProviderInterface(): void
    {
        $args = $this->makeArgs(identityMap: [
            'Some\Entity' => ['id1' => new \stdClass()],
        ]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');
        $this->messageBus->expects(self::never())->method('dispatch');

        $this->listener->postFlush($args);
    }

    public function testPostFlushDispatchesSyncDomainEventsOnlyViaEventDispatcher(): void
    {
        $event = $this->createStub(DomainEventInterface::class);
        $provider = $this->createStub(DomainEventProviderInterface::class);
        $provider->method('releaseDomainEvents')->willReturn([$event]);

        $args = $this->makeArgs(identityMap: [
            'App\Entity\Anything' => ['id1' => $provider],
        ]);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, $event::class)
            ->willReturn($event);

        $this->messageBus->expects(self::never())->method('dispatch');

        $this->listener->postFlush($args);
    }

    public function testPostFlushDispatchesAsyncDomainEventsToEventDispatcherAndMessageBus(): void
    {
        $asyncEvent = $this->createStub(AsyncDomainEventInterface::class);
        $provider = $this->createStub(DomainEventProviderInterface::class);
        $provider->method('releaseDomainEvents')->willReturn([$asyncEvent]);

        $args = $this->makeArgs(identityMap: [
            'App\Entity\Anything' => ['id1' => $provider],
        ]);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($asyncEvent, $asyncEvent::class)
            ->willReturn($asyncEvent);

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->with($asyncEvent);

        $this->listener->postFlush($args);
    }

    public function testPostFlushSkipsNonDomainEventItemsFromProvider(): void
    {
        $provider = $this->createStub(DomainEventProviderInterface::class);
        $provider->method('releaseDomainEvents')->willReturn([new \stdClass()]);

        $args = $this->makeArgs(identityMap: [
            'App\Entity\Anything' => ['id1' => $provider],
        ]);

        $this->eventDispatcher->expects(self::never())->method('dispatch');
        $this->messageBus->expects(self::never())->method('dispatch');

        $this->listener->postFlush($args);
    }

    /**
     * @param array<string, array<string, object>> $identityMap
     */
    private function makeArgs(array $identityMap): PostFlushEventArgs
    {
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getIdentityMap')->willReturn($identityMap);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);

        return new PostFlushEventArgs($em);
    }
}
