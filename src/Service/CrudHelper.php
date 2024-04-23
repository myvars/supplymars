<?php

namespace App\Service;

use App\Strategy\CrudStrategyInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\UnicodeString;
use Symfony\UX\Turbo\TurboBundle;

class CrudHelper extends AbstractController
{
    public const CRUD_BASE_TEMPLATE = 'crud/crud.html.twig';
    public const TURBO_STREAM_REFRESH_TEMPLATE = 'common/turboStreamRefresh.html.twig';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Paginator $paginator,
        private CrudStrategyInterface $crudStrategy,
        private string $section = '',
    ) {
    }

    public function renderIndex(
        ServiceEntityRepositoryInterface $repository,
        array $sortOptions = [],
        string $sortDefault = 'id'
    ): Response {
        $request = $this->requestStack->getCurrentRequest();
        $query = $request->query->get('query');
        $sort = $request->query->get('sort');
        $sort = in_array($sort, $sortOptions) ? $sort : $sortDefault;
        $sortDirection = $request->query->get('sortDirection') ?: 'ASC';
        $limit = $request->query->get('limit') ?: 5;
        $page = $request->query->get('page') ?: 1;

        try {
            $pager = $this->paginator->createPagination(
                $repository,
                $query,
                $sort,
                $sortDirection,
                $limit,
                $page
            );
        } catch (OutOfRangeCurrentPageException $e) {
            $this->addFlash(
                'warning',
                'Page '.$page.' could not be found!'
            );

            return $this->redirectToRoute(
                'app_'.$this->getSnakeSection().'_index',
                [
                    'page' => 1,
                    'limit' => $limit,
                    'sort' => $sort,
                    'sortDirection' => $sortDirection,
                    'query' => $query,
                ],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => 'index',
            'results' => $pager,
        ]);
    }

    public function renderCreate(
        ?object $entity,
        string $formType,
        int $formColumns = 1
    ): Response {
        if (!$entity) {
            return $this->crudError();
        }

        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl('app_'.$this->getSnakeSection().'_new'),
        ]);
        $form->handleRequest($this->requestStack->getCurrentRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->crudStrategy->create($entity);
                $this->addFlash(
                    'success',
                    'New '.$this->getSection().' added!'
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Can not add '.$this->getSection().'!'
                );
            }

            if ($this->requestStack->getCurrentRequest()->headers->has('turbo-frame')) {
                return $this->streamRefresh();
            }

            return $this->redirectToRoute(
                'app_'.$this->getSnakeSection().'_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => 'new',
            'result' => $entity,
            'form' => $form,
            'formColumns' => $formColumns,
        ]);
    }

    public function renderShow(?object $entity): Response
    {
        if (!$entity) {
            return $this->renderShowEmpty($this->getSection());
        }

        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => 'show',
            'result' => $entity,
        ]);
    }

    public function renderShowEmpty(string $section) : Response
    {
        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $section,
            'template' => 'show_empty'
        ]);
    }

    public function renderUpdate(
        ?object $entity,
        string $formType,
        int $formColumns = 1
    ): Response {
        if (!$entity) {
            return $this->crudError();
        }

        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl('app_'.$this->getSnakeSection().'_edit', ['id' => $entity->getId()]),
        ]);

        $successResponse = $this->redirectToRoute(
            'app_'.$this->getSnakeSection().'_index',
            [],
            Response::HTTP_SEE_OTHER
        );

        $backLink = $this->generateUrl('app_'.$this->getSnakeSection().'_index');

        return $this->renderCustomUpdate(
            $this->getSection(),
            $entity,
            $form,
            $successResponse,
            $backLink,
            $formColumns,
            true
        );
    }

    public function renderCustomUpdate(
        string $section,
        ?object $entity,
        FormInterface $form,
        RedirectResponse $successResponse,
        string $returnLink,
        int $formColumns = 1,
        bool $allowDelete = false,
    ): Response {
        if (!$this->section) {
            return $this->crudError();
        }

        if (!$entity) {
            return $this->crudError();
        }

        $form->handleRequest($this->requestStack->getCurrentRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->crudStrategy->update($entity);
                $this->addFlash(
                    'success',
                    $this->getSection().' updated!'
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Can not update '.$this->getSection().'!'
                );
            }

            if ($this->requestStack->getCurrentRequest()->headers->has('turbo-frame')) {
                return $this->streamRefresh();
            }

            return $successResponse;
        }

        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $section,
            'template' => 'edit',
            'result' => $entity,
            'form' => $form,
            'returnLink' => $returnLink,
            'allowDelete' => $allowDelete,
            'formColumns' => $formColumns,
        ]);
    }

    public function renderDeleteConfirm(?object $entity): Response
    {
        if (!$entity) {
            return $this->crudError();
        }

        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => 'delete',
            'result' => $entity,
        ]);
    }

    public function renderDelete(
        ?object $entity,
    ): Response {
        if (!$entity) {
            return $this->crudError();
        }

        if ($this->isCsrfTokenValid(
            'delete'.$entity->getId(),
            $this->requestStack->getCurrentRequest()->request->get('_token'))
        ) {
            try {
                $this->crudStrategy->delete($entity);
                $this->addFlash(
                    'success',
                    $this->getSection().' deleted!'
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Can not delete '.$this->getSection().', it has dependents!'
                );
            }

            if ($this->requestStack->getCurrentRequest()->headers->has('turbo-frame')) {
                return $this->streamRefresh();
            }
        }

        return $this->redirectToRoute(
            'app_'.$this->getSnakeSection().'_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }

    public function setStrategy(CrudStrategyInterface $crudStrategy): void
    {
        $this->crudStrategy = $crudStrategy;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function setSection(string $section): void
    {
        $this->section = $section;
    }

    private function getSnakeSection(): string
    {
        return (new UnicodeString($this->getSection()))->lower()->snake();
    }

    public function crudError(): Response
    {
        $this->addFlash(
            'warning',
            $this->getSection().' not found!'
        );

        return $this->redirectToRoute(
            'app_'.$this->getSnakeSection().'_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }

    public function streamRefresh(): Response
    {
        $this->requestStack->getCurrentRequest()->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->renderBlock(
            self::TURBO_STREAM_REFRESH_TEMPLATE,
            'stream_success'
        );
    }
}
