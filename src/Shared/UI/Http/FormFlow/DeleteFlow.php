<?php

namespace App\Shared\UI\Http\FormFlow;

use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use App\Shared\UI\Http\FormFlow\View\TemplateContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

/**
 * Coordinates delete confirmation and delete POST for a model.
 * Handles CSRF validation, user feedback, and Turbo‑aware redirects.
 */
final readonly class DeleteFlow
{
    public function __construct(
        private Environment $twig,
        private FlashMessenger $flashes,
        private CsrfTokenManagerInterface $csrf,
        private CommandFlow $flow,
    ) {
    }

    /**
     * Render the delete confirmation page.
     */
    public function deleteConfirm(object $entity, FlowContext $context): Response
    {
        $context->validate();

        // Build a consistent set of Twig variables for template.
        $templateContext = TemplateContext::from(
            $context->getFlowModel(),
            $context->getOperation()->value,
            $context->getTemplate(),
            $context->getRoutes(),
        );

        $html = $this->twig->render(FlowModel::BASE_TEMPLATE, array_merge(
            $templateContext->toArray(),
            ['result' => $entity],
        ));

        return new Response($html, Response::HTTP_OK);
    }

    /**
     * Process the delete POST.
     *
     * Validates CSRF then delegates to CommandFlow.
     *
     * @param object&object{id: int|string} $command
     */
    public function delete(
        Request $request,
        object $command,
        callable $handler,
        FlowContext $context,
    ): Response {
        $submitted = (string) $request->request->get('_token', '');
        $valid = $this->csrf->isTokenValid(new CsrfToken('delete' . $command->id, $submitted));

        if (!$valid) {
            $this->flashes->error($request, 'Invalid CSRF token.');

            // Invalid token => just go back to the success URL
            return $this->flow->successRedirect($request, $context);
        }

        return $this->flow->process(
            request: $request,
            command: $command,
            handler: $handler,
            context: $context,
        );
    }
}
