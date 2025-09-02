<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\Event\StatusWasChangedEventInterface;
use App\EventListener\LogStatusWasChanged;
use App\Service\OrderProcessing\StatusChangedLogger;
use App\Service\Utility\CurrentUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class LogStatusWasChangedTest extends TestCase
{
    public function testInvokeCallsLoggerWithEventAndCurrentUser(): void
    {
        $event = $this->createMock(StatusWasChangedEventInterface::class);
        $logger = $this->createMock(StatusChangedLogger::class);
        $user = $this->createMock(User::class);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $currentUserProvider = new CurrentUserProvider($security);

        $logger->expects($this->once())
            ->method('fromStatusWasChangedEvent')
            ->with($event, $user);

        $listener = new LogStatusWasChanged($logger, $currentUserProvider);
        $listener->__invoke($event);
    }
}
