<?php

namespace App\Shared\UI\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * Thin wrapper around Symfony flash bag for consistent success/error/warning messages.
 */
final class FlashMessenger
{
    public function success(Request $request, ?string $message): void
    {
        if ($message === null) {
            return;
        }

        $request->getSession()->getFlashBag()->add('success', $message);
    }

    public function warning(Request $request, ?string $message): void
    {
        if ($message === null) {
            return;
        }

        $request->getSession()->getFlashBag()->add('warning', $message);
    }

    public function error(Request $request, ?string $message): void
    {
        if ($message === null) {
            return;
        }

        $request->getSession()->getFlashBag()->add('danger', $message);
    }
}
