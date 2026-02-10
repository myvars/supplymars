<?php

namespace App\Shared\UI\Http\FormFlow;

use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\Paginator;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use App\Shared\UI\Http\FormFlow\View\TemplateContext;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Renders an index view with pagination and handles out‑of‑range page requests.
 */
final readonly class SearchFlow
{
    public function __construct(
        private Paginator $paginator,
        private Environment $twig,
        private FlashMessenger $flashes,
        private RedirectorInterface $redirector,
        private UrlGeneratorInterface $urls,
    ) {
    }

    public function search(
        Request $request,
        FindByCriteriaInterface $repository,
        SearchCriteriaInterface $criteria,
        FlowContext $context,
    ): Response {
        $context->validateForSearch();
        $operation = $context->getOperation()->value;

        try {
            $pagination = $this->paginator->searchPagination($repository, $criteria);
        } catch (OutOfRangeCurrentPageException) {
            $this->flashes->warning($request, 'Page ' . $criteria->getPage() . ' not found.');
            $url = $this->urls->generate($context->getRoutes()->index, array_merge(
                $request->query->all(),
                ['page' => '1'],
            ));

            return $this->redirector->to($request, $url);
        }

        $templateContext = TemplateContext::from(
            $context->getFlowModel(),
            $operation,
            routes: $context->getRoutes(),
        );

        $html = $this->twig->render(FlowModel::BASE_TEMPLATE, array_merge(
            $templateContext->toArray(),
            ['results' => $pagination],
        ));

        return new Response($html, Response::HTTP_OK);
    }
}
