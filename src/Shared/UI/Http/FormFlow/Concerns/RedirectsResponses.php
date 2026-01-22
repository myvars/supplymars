<?php

namespace App\Shared\UI\Http\FormFlow\Concerns;

use App\Shared\Application\RedirectTarget;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Shared redirect logic for flow classes.
 *
 * Classes using this trait must have:
 * - private RedirectorInterface $redirector
 * - private UrlGeneratorInterface $urls
 */
trait RedirectsResponses
{
    abstract private function getRedirector(): RedirectorInterface;

    abstract private function getUrlGenerator(): UrlGeneratorInterface;

    /**
     * Redirect to the configured success URL, using Turbo stream when applicable.
     */
    public function successRedirect(Request $request, FlowContext $context): Response
    {
        return $this->getRedirector()->to(
            $request,
            $context->resolveSuccessUrl($request, $this->getUrlGenerator()),
            $context->isRedirectRefresh(),
            $context->getRedirectStatus()
        );
    }

    /**
     * Redirect to a target URL, using Turbo stream when applicable.
     */
    public function redirectToTarget(Request $request, RedirectTarget $redirect): Response
    {
        return $this->getRedirector()->to(
            $request,
            $this->getUrlGenerator()->generate($redirect->route, $redirect->params),
            $redirect->redirectRefresh,
            $redirect->redirectStatus,
        );
    }
}
