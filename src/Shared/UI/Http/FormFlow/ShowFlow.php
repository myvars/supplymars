<?php

namespace App\Shared\UI\Http\FormFlow;

use App\Shared\UI\Http\FormFlow\View\ModelPath;
use App\Shared\UI\Http\FormFlow\View\TemplateContext;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Minimal read‑only renderer for a single resource view.
 */
final readonly class ShowFlow
{
    public const string OPERATION = 'show';

    public function __construct(private Environment $twig)
    {
    }

    /**
     * @param array<string, mixed> $extraVars
     */
    public function show(string $model, array $extraVars = [], ?string $template = null): Response
    {
        // Build a consistent set of Twig variables for template.
        $templateContext = TemplateContext::from($model, self::OPERATION, $template);

        $html = $this->twig->render(ModelPath::BASE_TEMPLATE, array_merge(
            $templateContext->toArray(),
            $extraVars
        ));

        return new Response($html, Response::HTTP_OK);
    }
}
