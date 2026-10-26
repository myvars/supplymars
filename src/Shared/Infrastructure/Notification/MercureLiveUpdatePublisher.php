<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use App\Shared\Application\LiveUpdatePublisherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * A refresh is public: it carries no data, so any staff browser may receive it, and each viewer's reload goes
 * through the page's access check. Rendered HTML is private, so the hub sends it only to browsers whose cookie
 * grants the topic: the user's own. See ADR 017.
 */
final readonly class MercureLiveUpdatePublisher implements LiveUpdatePublisherInterface
{
    /**
     * Morph, not the page's own replace, so what protects itself (data-turbo-permanent) survives: a form being
     * filled in, an open modal, an inline edit. Turbo debounces refreshes, so a burst costs one request per viewer.
     */
    public const string REFRESH_STREAM = '<turbo-stream action="refresh" method="morph" scroll="preserve"></turbo-stream>';

    public function __construct(
        private HubInterface $hub,
        private LoggerInterface $logger,
    ) {
    }

    public function refresh(string $topic): void
    {
        $this->send(new Update($topic, self::REFRESH_STREAM, private: false));
    }

    public function publish(string $topic, string $turboStream): void
    {
        $this->send(new Update($topic, $turboStream, private: true));
    }

    private function send(Update $update): void
    {
        try {
            $this->hub->publish($update);
        } catch (\Throwable $throwable) {
            $this->logger->error('Live update not published', [
                'topic' => $update->getTopics()[0],
                'exception' => $throwable,
            ]);
        }
    }
}
