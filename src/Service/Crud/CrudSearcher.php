<?php

namespace App\Service\Crud;

use App\DTO\SearchDto\SearchInterface;
use App\Repository\SearchQueryInterface;
use App\Service\Crud\Common\CrudHelper;
use App\Service\Search\Paginator;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CrudSearcher extends AbstractController
{
    public const string TEMPLATE = 'index';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly Paginator $paginator,
    ) {
    }

    public function search(
        string $section,
        SearchInterface $searchDto,
        SearchQueryInterface $searchQuery,
        ?array $queryParams,
    ): Response {
        try {
            $searchResults = $this->getSearchResults($searchDto, $searchQuery);
        } catch (OutOfRangeCurrentPageException) {
            $this->addFlash('warning', 'Page '.$searchDto->getPage().' not found!');

            return $this->crudHelper->redirectToLink(
                $this->generateUrl(
                    'app_'.$this->crudHelper->snakeCase($section).'_index',
                    array_merge($queryParams, ['page' => 1])
                )
            );
        }

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => $section,
            'template' => self::TEMPLATE,
            'results' => $searchResults,
        ]);
    }

    private function getSearchResults(
        SearchInterface $searchDto,
        SearchQueryInterface $searchQuery,
    ): Pagerfanta {
        return $this->paginator->createPagination(
            $searchQuery->findBySearchDto($searchDto),
            $searchDto->getPage() ?: 1,
            $searchDto->getLimit() ?: $searchDto::LIMIT_DEFAULT
        );
    }
}
