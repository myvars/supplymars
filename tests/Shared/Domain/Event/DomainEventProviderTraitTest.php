<?php

namespace App\Tests\Shared\Domain\Event;

use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use PHPUnit\Framework\TestCase;

final class DomainEventProviderTraitTest extends TestCase
{
    private function stubEvent(): AbstractDomainEvent
    {
        return $this->createStub(AbstractDomainEvent::class);
    }

    public function testRaiseAndReleaseReturnsAndClearsQueue(): void
    {
        $aggregate = new class {
            use DomainEventProviderTrait;
        };

        $e1 = $this->stubEvent();
        $e2 = $this->stubEvent();

        $aggregate->raiseDomainEvent($e1);
        $aggregate->raiseDomainEvent($e2);

        $released = $aggregate->releaseDomainEvents();
        self::assertSame([$e1, $e2], $released);

        self::assertSame([], $aggregate->releaseDomainEvents());
    }
}
