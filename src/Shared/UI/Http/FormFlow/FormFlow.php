<?php

namespace App\Shared\UI\Http\FormFlow;

use App\Shared\Application\RedirectTarget;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\Concerns\RedirectsResponses;
use App\Shared\UI\Http\FormFlow\Guard\AutoUpdateGuard;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\ModelPath;
use App\Shared\UI\Http\FormFlow\View\TemplateContext;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Generic form coordinator for create/update operations.
 * Centralizes form creation, validation, mapping, handler invocation, feedback, and redirect.
 */
final readonly class FormFlow
{
    use RedirectsResponses;

    public function __construct(
        private FormFactoryInterface $forms,
        private FlashMessenger $flashes,
        private Environment $twig,
        private UrlGeneratorInterface $urls,
        private RedirectorInterface $redirector,
        private AutoUpdateGuard $autoUpdateForm,
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
     * Handle GET/POST for a Symfony form and redirect on success.
     *
     * On invalid submission, re‑renders the form with validation errors.
     * On success, flashes feedback and redirects (Turbo‑aware).
     */
    public function form(
        Request $request,
        string $formType,
        mixed $data,
        callable $mapper,
        callable $handler,
        FlowContext $context,
        array $formOptions = [],
    ): Response {
        // Validate preset preconditions up front.
        $context->validate();

        // Default the form action to the current URL (Turbo‑friendly).
        $formOptions['action'] ??= $request->getUri();

        $form = $this->forms->create($formType, $data, $formOptions);
        $form->handleRequest($request);

        // Compute HTTP status for rendering branch.
        $status = $this->getResponseStatus($form);

        // Success path: submitted, valid, and not an auto‑update submit.
        if ($form->isSubmitted() && $form->isValid() && !$this->autoUpdateForm->is($form)) {
            $command = $this->map($mapper, $form->getData());
            $result = $handler($command);

            // Handler reported success
            if ($result->ok) {
                $this->flashes->success($request, $result->message);

                // Redirect to forced target if given.
                if ($result->redirect instanceof RedirectTarget) {
                    return $this->redirectToTarget($request, $result->redirect);
                }

                return $this->successRedirect($request, $context);
            }

            // Handler reported failure -> 422 and error flash.
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $this->flashes->error($request, $result->message);
        }

        // In auto‑update scenarios clear blocking errors to keep UX responsive.
        $this->autoUpdateForm->clear($form);

        // GET or invalid POST fall through to render.
        return $this->render($request, $context, $form, $status, $data);
    }

    /**
     * Determine HTTP status for rendering the form page.
     */
    private function getResponseStatus(FormInterface $form): int
    {
        if ($form->isSubmitted() && !$form->isValid() && !$this->autoUpdateForm->is($form)) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        return Response::HTTP_OK;
    }

    /**
     * Map form data to a command object via callable.
     *
     * @throws \LogicException When mapper does not return an object
     */
    private function map(callable $mapper, mixed $data): object
    {
        $command = $mapper($data);
        if (!\is_object($command)) {
            throw new \LogicException('Mapper must return an object.');
        }

        return $command;
    }

    /**
     * Render the base template.
     */
    private function render(
        Request $request,
        FlowContext $context,
        FormInterface $form,
        int $status,
        mixed $data,
    ): Response {
        // Build a consistent set of Twig variables for template.
        $templateContext = TemplateContext::from(
            $context->getModel(),
            $context->getOperation()->value,
            $context->getTemplate(),
        );

        $html = $this->twig->render(ModelPath::BASE_TEMPLATE, array_merge(
            $templateContext->toArray(),
            [
                'flowBackLink' => $context->resolveBackUrl($request),
                'flowAllowDelete' => $context->isAllowDelete(),
                'form' => $form->createView(),
                'result' => $data,
            ]
        ));

        return new Response($html, $status);
    }
}
