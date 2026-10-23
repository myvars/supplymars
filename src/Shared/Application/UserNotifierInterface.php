<?php

declare(strict_types=1);

namespace App\Shared\Application;

/**
 * Shows one user a toast on whatever page they have open, for something another user or a background job
 * did. Fire-and-forget: a user with no page open never sees it, so anything that must not be lost belongs
 * in email or a stored record. See Docs/patterns/Turbo/README.md#live-updates-with-mercure.
 */
interface UserNotifierInterface
{
    /**
     * Never throws when the hub fails: it logs, so a missed toast cannot fail the caller or its message.
     *
     * @param string $type a flash type: success, info, warning or danger
     * @param string $url  shown as the toast's "View" link
     */
    public function notify(string $userId, string $type, string $message, ?string $url = null): void;
}
