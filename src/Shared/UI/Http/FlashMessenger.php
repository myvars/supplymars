<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use MyVars\FormFlow\Contract\FlasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/**
 * Thin wrapper around Symfony flash bag for consistent success/error/warning messages.
 *
 * Flash keys match the types FlashToast accepts (see templates/_flashes.html.twig):
 * - success() → 'success'
 * - warning() → 'warning'
 * - error()   → 'danger' (the key is 'danger', not 'error')
 */
final class FlashMessenger implements FlasherInterface
{
    public const string SUCCESS_KEY = 'success';

    public const string WARNING_KEY = 'warning';

    public const string ERROR_KEY = 'danger';

    public function success(Request $request, ?string $message): void
    {
        if ($message === null) {
            return;
        }

        $this->getFlashBag($request)->add(self::SUCCESS_KEY, $message);
    }

    public function warning(Request $request, ?string $message): void
    {
        if ($message === null) {
            return;
        }

        $this->getFlashBag($request)->add(self::WARNING_KEY, $message);
    }

    /**
     * Add error flash message.
     *
     * Note: Uses the 'danger' key, the type FlashToast renders as an error.
     */
    public function error(Request $request, ?string $message): void
    {
        if ($message === null) {
            return;
        }

        $this->getFlashBag($request)->add(self::ERROR_KEY, $message);
    }

    private function getFlashBag(Request $request): FlashBagInterface
    {
        $session = $request->getSession();
        assert($session instanceof FlashBagAwareSessionInterface);

        return $session->getFlashBag();
    }
}
