<?php

namespace App\Shared\UI\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * Thin wrapper around Symfony flash bag for consistent success/error/warning messages.
 *
 * Flash keys are aligned with Bootstrap alert classes:
 * - success() → 'success' (green alert)
 * - warning() → 'warning' (yellow alert)
 * - error()   → 'danger'  (red alert, Bootstrap uses 'danger' not 'error')
 */
final class FlashMessenger
{
    public const string SUCCESS_KEY = 'success';

    public const string WARNING_KEY = 'warning';

    public const string ERROR_KEY = 'danger';

    public function success(Request $request, ?string $message): void
    {
        if ($message === null) {
            return;
        }

        $request->getSession()->getFlashBag()->add(self::SUCCESS_KEY, $message);
    }

    public function warning(Request $request, ?string $message): void
    {
        if ($message === null) {
            return;
        }

        $request->getSession()->getFlashBag()->add(self::WARNING_KEY, $message);
    }

    /**
     * Add error flash message.
     *
     * Note: Uses 'danger' key to align with Bootstrap alert-danger class.
     */
    public function error(Request $request, ?string $message): void
    {
        if ($message === null) {
            return;
        }

        $request->getSession()->getFlashBag()->add(self::ERROR_KEY, $message);
    }
}
