<?php

namespace App\Shared\UI\Http\FormFlow;

use App\Shared\Application\RedirectTarget;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Coordinates delete confirmation and delete POST for a model.
 * Handles CSRF validation, user feedback, and Turbo‑aware redirects.
 */
final readonly class CommandFlow
{
    public function __construct(
        private FlashMessenger $flashes,
        private RedirectorInterface $redirector,
        private UrlGeneratorInterface $urls
    ) {
    }

    /**
     * Delegates to handler, maps known failures to user messages,
     * and always finishes with a redirect (Turbo stream or normal).
     */
    public function process(
        Request $request,
        object $command,
        callable $handler,
        FlowContext $context
    ): Response {
        // Delegate to the application‑level handler.
        $result = $handler($command);

        // Handler reported success
        if ($result->ok) {
            $this->flashes->success($request, $result->message);
        } else {
            $this->flashes->error($request, $result->message);
        }

        // Redirect to forced target if given.
        if ($result->redirect instanceof RedirectTarget) {
            return $this->redirectToTarget($request, $result->redirect);
        }

        return $this->successRedirect($request, $context);
    }

    /**
     * Redirect to the configured success URL, using Turbo stream when applicable.
     */
    public function successRedirect(Request $request, FlowContext $preset): Response
    {
        return $this->redirector->to(
            $request,
            $preset->resolveSuccessUrl($request, $this->urls),
            $preset->isRedirectRefresh(),
            $preset->getRedirectStatus()
        );
    }

    /**
     * Redirect to a target URL, using Turbo stream when applicable.
     */
    public function redirectToTarget(Request $request, RedirectTarget $redirect): Response
    {
        return $this->redirector->to(
            $request,
            $this->urls->generate($redirect->route, $redirect->params),
            $redirect->redirectRefresh,
            $redirect->redirectStatus,
        );
    }
}
