<?php

namespace App\Shared\UI\Http\FormFlow\Redirect;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstraction for redirects that works with Turbo and non‑Turbo requests.
 * Implementations decide whether to emit a Turbo stream or a classic redirect.
 */
interface RedirectorInterface
{
    /**
     * Redirect to $url. When $refresh is true and the request is Turbo,
     * emit a Turbo stream response that refreshes the current frame.
     */
    public function to(Request $request, string $url, bool $refresh = false, int $status = 303): Response;
}
