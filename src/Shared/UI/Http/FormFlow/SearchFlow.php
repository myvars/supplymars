<?php

namespace App\Shared\UI\Http\FormFlow;

use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\Paginator;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\ModelPath;
use App\Shared\UI\Http\FormFlow\View\TemplateContext;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Renders an index view with pagination and handles out‑of‑range page requests
 */
final readonly class SearchFlow
{
    public const string OPERATION = 'index';

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
        string $model,
        FindByCriteriaInterface $repository,
        SearchCriteriaInterface $criteria,
    ): Response {
        try {
            $pagination = $this->paginator->searchPagination($repository, $criteria);
        } catch (OutOfRangeCurrentPageException) {
            $this->flashes->warning($request, 'Page ' . $criteria->getPage() . ' not found.');
            $url = $this->urls->generate('app_' . ModelPath::route($model) . '_index', array_merge(
                $request->query->all(),
                ['page' => $criteria::PAGE_DEFAULT],
            ));

            return $this->redirector->to($request, $url);
        }

        $templateContext = TemplateContext::from($model, self::OPERATION);
        $html = $this->twig->render(ModelPath::BASE_TEMPLATE, array_merge(
            $templateContext->toArray(),
            ['results' => $pagination],
        ));

        return new Response($html, Response::HTTP_OK);
    }
}
