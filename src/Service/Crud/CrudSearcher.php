<?php

namespace App\Service\Crud;

use App\DTO\SearchDto\SearchInterface;
use App\Repository\SearchQueryInterface;
use App\Service\Search\Paginator;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CrudSearcher extends AbstractController
{
    public const TEMPLATE = 'index';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly Paginator $paginator
    ) {
    }

    public function search(
        string $section,
        SearchInterface $searchDto,
        SearchQueryInterface $searchQuery,
        ?array $queryParams,
    ): Response {
        try {
            $queryBuilder = $searchQuery->findBySearchDto($searchDto);
            $pager = $this->paginator->createPagination(
                $queryBuilder,
                $searchDto->getPage() ?: 1,
                $searchDto->getLimit() ?: $searchDto::LIMIT_DEFAULT
            );
        } catch (OutOfRangeCurrentPageException) {
            $this->addFlash(
                'warning',
                'Page '. $searchDto->getPage() .' not found!'
            );

            return $this->crudHelper->redirectToLink($this->generateUrl(
                    'app_'.$this->crudHelper->snakeCase($section).'_index',
                    array_merge($queryParams, ['page' => 1])
            ));
        }

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => $section,
            'template' => self::TEMPLATE,
            'results' => $pager,
        ]);
    }
}