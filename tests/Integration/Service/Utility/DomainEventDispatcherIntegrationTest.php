<?php

namespace App\Tests\Integration\Service\Utility;

use App\Factory\CustomerOrderFactory;
use App\Service\Utility\DomainEventDispatcher;
use App\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Zenstruck\Foundry\Test\Factories;

class DomainEventDispatcherIntegrationTest extends KernelTestCase
{
    use Factories;

    private DomainEventDispatcher $domainEventDispatcher;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->domainEventDispatcher = new DomainEventDispatcher($this->eventDispatcher, static::getContainer()->get(Security::class));
        StaffUserStory::load();
    }

    public function testDispatchProviderEvents(): void
    {
        $customerOrder = CustomerOrderFactory::new()->create()->_real();
        $customerOrder->cancelOrder();

        $event = $customerOrder->releaseDomainEvents()[0];
        $customerOrder->raiseDomainEvent($event);

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with($event);

        $this->domainEventDispatcher->dispatchProviderEvents($customerOrder);
    }
}