<?php

namespace App\Service\Crud;

use App\Service\Crud\Core\CrudIndexOptions;
use App\Service\Crud\Core\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CrudIndexer extends AbstractController
{
    public const TEMPLATE = 'index';

    public function __construct(
        public readonly CrudHelper $crudHelper,
        private readonly CrudIndexOptions $crudOptions,
        private readonly Paginator $paginator
    ) {
    }

    public function index(
        string $section,
        ServiceEntityRepositoryInterface $repository,
        array $sortOptions = []
    ): Response {
        $crudOptions = $this->createOptions($section, $repository, $sortOptions);

        return $this->build($crudOptions);
    }

    public function createOptions(
        string $section,
        ServiceEntityRepositoryInterface $repository,
        array $sortOptions = []
    ): CrudIndexOptions {
        $request = $this->crudHelper->getRequest();
        return $this->resetOptions()
            ->setSection($section)
            ->setRepository($repository)
            ->setSortOptions($sortOptions)
            ->setQuery($request->query->get('query'))
            ->setSortDefault($this->crudOptions::SORT_DEFAULT)
            ->setSort($request->query->get('sort'))
            ->setSortDirectionDefault($this->crudOptions::SORT_DIRECTION_DEFAULT)
            ->setSortDirection($request->query->get('sortDirection'))
            ->setLimit($request->query->get('limit') ?: $this->crudOptions::LIMIT_DEFAULT)
            ->setPage($request->query->get('page') ?: $this->crudOptions::PAGE_DEFAULT);
    }

    public function build(CrudIndexOptions $crudOptions): Response
    {
        $repository = $crudOptions->getRepository();
        try {
            $queryBuilder = $repository->findBySearchQueryBuilder(
                $crudOptions->getQuery(),
                $crudOptions->getSort() ?: $crudOptions->getSortDefault(),
                $crudOptions->getSortDirection() ?: $crudOptions->getSortDirectionDefault()
            );
            $pager = $this->paginator->createPagination(
                $queryBuilder,
                $crudOptions->getPage(),
                $crudOptions->getLimit()
            );
        } catch (OutOfRangeCurrentPageException $e) {
            $this->addFlash(
                'warning',
                'Page '.$crudOptions->getPage().' could not be found!'
            );

            return $this->crudHelper->redirectToRoute(
                'app_'.$this->crudHelper->snakeCase($crudOptions->getSection()).'_index',
                [
                    'page' => 1,
                    'limit' => $crudOptions->getLimit(),
                    'sort' => $crudOptions->getSort() ?: $crudOptions->getSortDefault(),
                    'sortDirection' => $crudOptions->getSortDirection() ?: $crudOptions->getSortDirectionDefault(),
                    'query' => $crudOptions->getQuery(),
                ],
            );
        }

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => $crudOptions->getSection(),
            'template' => self::TEMPLATE,
            'results' => $pager,
        ]);
    }

    public function resetOptions(): CrudIndexOptions
    {
        return $this->crudOptions::create();
    }
}
