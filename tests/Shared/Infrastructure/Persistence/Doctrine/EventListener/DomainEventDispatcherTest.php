<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine\EventListener;

use App\Shared\Domain\Event\AsyncDomainEventInterface;
use App\Shared\Domain\Event\DomainEventInterface;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\EventListener\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DomainEventDispatcherTest extends TestCase
{
    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $eventDispatcher;

    /**
     * @var MockObject&MessageBusInterface
     */
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

    public function testPostFlushDispatchesEventsFromEntitiesDeletedByTheFlush(): void
    {
        $event = $this->createStub(DomainEventInterface::class);
        $deleted = $this->createStub(DomainEventProviderInterface::class);
        $deleted->method('releaseDomainEvents')->willReturn([$event]);

        // The flush takes the deleted entity out of the identity map before postFlush.
        $em = $this->makeEntityManager(identityMap: [], scheduledDeletions: [$deleted, new \stdClass()]);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, $event::class)
            ->willReturn($event);

        $this->messageBus->expects(self::never())->method('dispatch');

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush(new PostFlushEventArgs($em));
    }

    public function testPostFlushReleasesEveryEntitysEventsBeforeDispatchingAny(): void
    {
        // A listener that flushes runs postFlush again. If the other entities' events were still
        // waiting, that nested run would dispatch them, one level deeper per entity.
        $calls = [];
        $first = $this->createStub(DomainEventInterface::class);
        $second = $this->createStub(DomainEventInterface::class);

        $firstProvider = $this->createStub(DomainEventProviderInterface::class);
        $firstProvider->method('releaseDomainEvents')->willReturnCallback(static function () use (&$calls, $first): array {
            $calls[] = 'release first';

            return [$first];
        });
        $secondProvider = $this->createStub(DomainEventProviderInterface::class);
        $secondProvider->method('releaseDomainEvents')->willReturnCallback(static function () use (&$calls, $second): array {
            $calls[] = 'release second';

            return [$second];
        });

        $this->eventDispatcher->expects(self::exactly(2))->method('dispatch')->willReturnCallback(static function (object $event) use (&$calls, $first): object {
            $calls[] = $event === $first ? 'dispatch first' : 'dispatch second';

            return $event;
        });
        $this->messageBus->expects(self::never())->method('dispatch');

        $this->listener->postFlush($this->makeArgs(identityMap: [
            'App\Entity\Anything' => ['id1' => $firstProvider, 'id2' => $secondProvider],
        ]));

        self::assertSame(['release first', 'release second', 'dispatch first', 'dispatch second'], $calls);
    }

    public function testPostFlushHoldsEventsFromEntitiesRemovedButNotYetFlushed(): void
    {
        // A listener removed the entity during this postFlush; a later flush deletes it.
        $pending = $this->createMock(DomainEventProviderInterface::class);
        $pending->expects(self::never())->method('releaseDomainEvents');

        $em = $this->makeEntityManager(
            identityMap: ['App\Entity\Anything' => ['id1' => $pending]],
            pendingDeletions: [$pending],
        );

        $this->eventDispatcher->expects(self::never())->method('dispatch');
        $this->messageBus->expects(self::never())->method('dispatch');

        $this->listener->postFlush(new PostFlushEventArgs($em));
    }

    public function testDeletedEntitiesFromAFailedFlushAreNotCarriedIntoTheNext(): void
    {
        $deleted = $this->createMock(DomainEventProviderInterface::class);
        $deleted->expects(self::never())->method('releaseDomainEvents');

        $failed = $this->makeEntityManager(identityMap: [], scheduledDeletions: [$deleted]);
        $next = $this->makeEntityManager(identityMap: []);

        $this->eventDispatcher->expects(self::never())->method('dispatch');
        $this->messageBus->expects(self::never())->method('dispatch');

        // The first flush fails after onFlush, so its postFlush never runs.
        $this->listener->onFlush(new OnFlushEventArgs($failed));
        $this->listener->onFlush(new OnFlushEventArgs($next));
        $this->listener->postFlush(new PostFlushEventArgs($next));
    }

    /**
     * @param array<string, array<string, object>> $identityMap
     */
    private function makeArgs(array $identityMap): PostFlushEventArgs
    {
        return new PostFlushEventArgs($this->makeEntityManager($identityMap));
    }

    /**
     * @param array<string, array<string, object>> $identityMap
     * @param list<object>                         $scheduledDeletions entities onFlush finds scheduled for deletion
     * @param list<object>                         $pendingDeletions   entities still scheduled for deletion in postFlush
     */
    private function makeEntityManager(array $identityMap, array $scheduledDeletions = [], array $pendingDeletions = []): EntityManagerInterface
    {
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getIdentityMap')->willReturn($identityMap);
        $uow->method('getScheduledEntityDeletions')->willReturn($scheduledDeletions);
        $uow->method('isScheduledForDelete')->willReturnCallback(
            static fn (object $entity): bool => \in_array($entity, $pendingDeletions, true),
        );

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);

        return $em;
    }
}
