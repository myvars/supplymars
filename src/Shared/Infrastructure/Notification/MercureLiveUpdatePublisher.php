<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use App\Shared\Application\LiveUpdatePublisherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/** Publishes each update as a private update, so the hub sends it only to browsers whose cookie grants the topic. */
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
        $this->publish($topic, self::REFRESH_STREAM);
    }

    public function publish(string $topic, string $turboStream): void
    {
        try {
            $this->hub->publish(new Update($topic, $turboStream, private: true));
        } catch (\Throwable $throwable) {
            $this->logger->error('Live update not published', [
                'topic' => $topic,
                'exception' => $throwable,
            ]);
        }
    }
}
