<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\Event\StatusWasChangedEventInterface;
use App\EventListener\LogStatusWasChanged;
use App\EventListener\PublicIdResolverRegistry;
use App\Service\OrderProcessing\StatusChangedLogger;
use App\Service\Utility\CurrentUserProvider;
use App\ValueObject\CustomerOrderPublicId;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LogStatusWasChangedTest extends TestCase
{
    public function testInvokeCallsLoggerWithEventAndCurrentUser(): void
    {
        $legacyId = 123;
        $publicId = CustomerOrderPublicId::new();

        $event = $this->createMock(StatusWasChangedEventInterface::class);
        $event->method('publicId')->willReturn($publicId);

        $user = $this->createMock(User::class);
        $currentUserProvider = $this->createStub(CurrentUserProvider::class);
        $currentUserProvider->method('get')->willReturn($user);

        $publicIdResolverRegistry = $this->createMock(PublicIdResolverRegistry::class);
        $publicIdResolverRegistry->method('resolve')->with($publicId)->willReturn($legacyId);

        $statusChangedLogger = $this->createMock(StatusChangedLogger::class);
        $statusChangedLogger->expects($this->once())
            ->method('fromStatusWasChangedEvent')
            ->with($event, $user, $legacyId);

        $logger = $this->createMock(LoggerInterface::class);

        $listener = new LogStatusWasChanged(
            $statusChangedLogger,
            $currentUserProvider,
            $publicIdResolverRegistry,
            $logger

        );

        $listener->__invoke($event);
    }
}
