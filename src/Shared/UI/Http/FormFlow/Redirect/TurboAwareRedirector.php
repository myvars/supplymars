<?php

namespace App\Shared\UI\Http\FormFlow\Redirect;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Turbo\TurboBundle;
use Twig\Environment;

/**
 * Turbo‑aware redirector that:
 *  - Detects Turbo requests via headers/format/accept.
 *  - Emits a Turbo stream refresh response when appropriate.
 *  - Falls back to a normal RedirectResponse otherwise.
 */
final readonly class TurboAwareRedirector implements RedirectorInterface
{
    public const string TURBO_STREAM_REFRESH_TEMPLATE = 'shared/turbo/turbo_stream_refresh.html.twig';

    private const string TURBO_STREAM_MIME = 'text/vnd.turbo-stream.html';

    public function __construct(private Environment $twig)
    {
    }

    /**
     * Redirect or produce a Turbo stream depending on the incoming request.
     * Keeps the `Content-Type` correct for Turbo so it processes the stream.
     */
    public function to(Request $request, string $url, bool $refresh = false, int $status = 303): Response
    {
        // Detect Turbo frame or stream request via headers or request format.
        $hasTurboFrameHeader = $request->headers->has('turbo-frame') || $request->headers->has('Turbo-Frame');

        $acceptHeader = (string) $request->headers->get('accept', '');
        $isTurboFormat = $request->getRequestFormat() === TurboBundle::STREAM_FORMAT
            || str_contains($acceptHeader, self::TURBO_STREAM_MIME);

        if ($hasTurboFrameHeader || $isTurboFormat) {
            // Tell Symfony to emit Turbo Stream.
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            try {
                $content = $this->twig->render(self::TURBO_STREAM_REFRESH_TEMPLATE, [
                    'newUrl' => $refresh ? $url : null,
                ]);
            } catch (\Throwable) {
                $content = '';
            }

            return new Response($content, Response::HTTP_OK);
        }

        // Regular (non-Turbo) fallback - emit a proper redirect so browser follows it.
        return new RedirectResponse($url, $status);
    }
}
