<?php

namespace App\Shared\UI\Http\FormFlow;

use App\Shared\Application\RedirectTarget;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\Concerns\RedirectsResponses;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Executes commands directly without forms (state transitions, actions).
 * Handles user feedback and Turbo‑aware redirects.
 */
final readonly class CommandFlow
{
    use RedirectsResponses;

    public function __construct(
        private FlashMessenger $flashes,
        private RedirectorInterface $redirector,
        private UrlGeneratorInterface $urls,
    ) {
    }

    private function getRedirector(): RedirectorInterface
    {
        return $this->redirector;
    }

    private function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->urls;
    }

    /**
     * Delegates to handler, maps known failures to user messages,
     * and always finishes with a redirect (Turbo stream or normal).
     */
    public function process(
        Request $request,
        object $command,
        callable $handler,
        FlowContext $context,
    ): Response {
        // Validate context preconditions up front.
        $context->validateForCommand();

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
}
