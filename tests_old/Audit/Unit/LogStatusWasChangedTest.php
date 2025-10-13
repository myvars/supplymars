<?php

namespace App\Tests\Audit\Unit;

use App\Audit\Application\EventListener\StatusChangeLogger;
use App\Audit\Infrastructure\Logging\StatusChangeLogWriter;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Shared\Application\Identity\PublicIdResolverRegistry;
use App\Shared\Domain\Event\StatusWasChangedEventInterface;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LogStatusWasChangedTest extends TestCase
{
    public function testInvokeCallsLoggerWithEventAndCurrentUser(): void
    {
        $legacyId = 123;
        $publicId = OrderPublicId::new();

        $event = $this->createMock(StatusWasChangedEventInterface::class);
        $event->method('publicId')->willReturn($publicId);

        $user = $this->createMock(User::class);
        $currentUserProvider = $this->createStub(CurrentUserProvider::class);
        $currentUserProvider->method('get')->willReturn($user);

        $publicIdResolverRegistry = $this->createMock(PublicIdResolverRegistry::class);
        $publicIdResolverRegistry->method('resolve')->with($publicId)->willReturn($legacyId);

        $statusChangedLogger = $this->createMock(StatusChangeLogWriter::class);
        $statusChangedLogger->expects($this->once())
            ->method('write')
            ->with($event, $user, $legacyId);

        $logger = $this->createMock(LoggerInterface::class);

        $listener = new StatusChangeLogger(
            $statusChangedLogger,
            $currentUserProvider,
            $publicIdResolverRegistry,
            $logger

        );

        $listener->__invoke($event);
    }
}
