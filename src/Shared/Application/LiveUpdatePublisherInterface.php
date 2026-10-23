<?php

declare(strict_types=1);

namespace App\Shared\Application;

/**
 * Sends a Turbo Stream to every page subscribed to a topic: a user's own (user/{id}, see UserNotifierInterface)
 * or a page's (order/{id}, declared as live_topics). Private: only a browser whose cookie grants the topic,
 * set by rendering a page the user may see, receives it. Fire-and-forget, like the notifier. See
 * Docs/patterns/Turbo/README.md#live-page-updates.
 */
interface LiveUpdatePublisherInterface
{
    /**
     * Every page on the topic reloads itself under its own viewer's login, keeping what the viewer is in the
     * middle of. Use it when the page has per-viewer content, as most do.
     */
    public function refresh(string $topic): void;

    /** Never throws when the hub fails: it logs, so a missed update cannot fail the caller or its message. */
    public function publish(string $topic, string $turboStream): void;
}
