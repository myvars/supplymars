<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * A customer's pages set no Mercure cookie (base.html.twig), so a customer who logs in on a browser still holding
 * a staff user's cookie, without that user logging out first, would otherwise keep it for up to a week and could
 * read their toasts. A staff login needs nothing: its next page sets the cookie for the new user, and clearing it
 * here too would throw when a remember-me login and that page are the same request.
 */
#[AsEventListener]
final readonly class ClearMercureCookieOnCustomerLogin
{
    public function __construct(
        private Authorization $authorization,
        private AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        if ($this->accessDecisionManager->decide($event->getAuthenticatedToken(), ['ROLE_ADMIN'])) {
            return;
        }

        $this->authorization->clearCookie($event->getRequest());
    }
}
