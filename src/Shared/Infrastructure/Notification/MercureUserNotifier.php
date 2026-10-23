<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use App\Shared\Application\LiveUpdatePublisherInterface;
use App\Shared\Application\UserNotifierInterface;
use Twig\Environment;

/**
 * Publishes a Turbo Stream that appends a toast to #flash-container, on the user's topic. base.html.twig
 * subscribes each logged-in page to its own user's topic.
 */
final readonly class MercureUserNotifier implements UserNotifierInterface
{
    public function __construct(
        private LiveUpdatePublisherInterface $publisher,
        private Environment $twig,
    ) {
    }

    public static function topic(string $userId): string
    {
        return 'user/' . $userId;
    }

    public function notify(string $userId, string $type, string $message, ?string $url = null): void
    {
        // Rendered before publishing: a template error is a bug, not a hub failure, so it throws
        $stream = $this->twig->render('shared/turbo/_user_notification_stream.html.twig', [
            'type' => $type,
            'message' => $message,
            'url' => $url,
        ]);

        $this->publisher->publish(self::topic($userId), $stream);
    }
}
