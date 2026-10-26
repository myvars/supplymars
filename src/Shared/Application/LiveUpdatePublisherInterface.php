<?php

declare(strict_types=1);

namespace App\Shared\Application;

/**
 * Sends a Turbo Stream to every page subscribed to a topic: a page's (order/{id}, declared as live_topics) or a
 * user's own (user/{id}, see UserNotifierInterface). Fire-and-forget, like the notifier: it never throws when
 * the hub fails, it logs, so a missed update cannot fail the caller or its message. See
 * Docs/patterns/Turbo/README.md#live-page-updates.
 */
interface LiveUpdatePublisherInterface
{
    /**
     * Every page on the topic reloads itself under its own viewer's login, keeping what the viewer is in the
     * middle of. Use it when the page has per-viewer content, as most do. Public: any staff browser may receive it,
     * which is safe because it carries no data and each viewer's reload goes through the page's own access check.
     */
    public function refresh(string $topic): void;

    /**
     * Private: only a browser whose cookie grants the topic receives it, and the cookie grants only the user's
     * own topic. Never content on a page topic (ADR 017).
     */
    public function publish(string $topic, string $turboStream): void;
}
