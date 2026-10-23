<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * The Mercure cookie outlives the session, so without this a shared browser would keep receiving the
 * previous user's toasts until it expired.
 */
#[AsEventListener]
final readonly class ClearMercureCookieOnLogout
{
    public function __construct(private Authorization $authorization)
    {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $this->authorization->clearCookie($event->getRequest());
    }
}
