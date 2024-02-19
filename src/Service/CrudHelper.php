<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\UnicodeString;
use Symfony\UX\Turbo\TurboBundle;

class CrudHelper extends AbstractController
{
    const string CRUD_BASE_TEMPLATE = 'crud/crud.html.twig';
    const string TURBO_STREAM_REFRESH_TEMPLATE = 'common/turboStreamRefresh.html.twig';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private string $section = '',
        private int $formColumns = 1,
    )
    {
    }

    public function renderIndex(
        QueryBuilder $queryBuilder,
        int $page = 1,
        int $limit = 10,
        string $sort = 'id',
        string $sortDirection = 'ASC',
        string $query = null,
    ): Response
    {
        try {
            $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
                new QueryAdapter($queryBuilder),
                $page,
                $limit
            );

        } catch (OutOfRangeCurrentPageException $e) {
            $this->addFlash(
                'warning',
                'Page ' . $page . ' could not be found!'
            );

            return $this->redirectToRoute(
                'app_' . $this->snakeSection() . '_index',
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
        Request $request,
        ?object $entity,
        string $formType
    ): Response
    {
        if (!$entity) {
            return $this->crudError();
        }

        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl('app_'.$this->snakeSection().'_new'),
//            'validation_groups' => $request->request->get('btnSubmit') !== 'submit' ? [''] : ['Default']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $this->entityManager->persist($entity);
                $this->entityManager->flush();

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

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock(
                    self::TURBO_STREAM_REFRESH_TEMPLATE,
                    'stream_success'
                );
            }

            return $this->redirectToRoute(
                'app_'.$this->snakeSection().'_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => 'new',
            'result' => $entity,
            'form' => $form,
            'formColumns' => $this->getFormColumns(),
        ]);
    }

    public function renderShow(?object $entity): Response
    {
        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => $entity ? 'show' : 'show_empty',
            'result' => $entity,
        ]);
    }

    public function renderUpdate(
        Request $request,
        ?object $entity,
        string $formType,
    ): Response
    {
        if (!$entity) {
            return $this->crudError();
        }

        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl('app_'.$this->snakeSection().'_edit', ['id' => $entity->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $this->entityManager->flush();

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

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock(
                    self::TURBO_STREAM_REFRESH_TEMPLATE,
                    'stream_success'
                );
            }

            return $this->redirectToRoute(
                'app_'.$this->snakeSection().'_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => 'edit',
            'result' => $entity,
            'form' => $form,
            'formColumns' => $this->getFormColumns()
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
        Request $request,
        ?object $entity,
    ): Response
    {
        if (!$entity) {
            return $this->crudError();
        }

        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token')))
        {
            try {
                $this->entityManager->remove($entity);
                $this->entityManager->flush();

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

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock(
                    self::TURBO_STREAM_REFRESH_TEMPLATE,
                    'stream_success'
                );
            }
        }

        return $this->redirectToRoute(
            'app_'.$this->snakeSection().'_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function setSection(string $section): void
    {
        $this->section = $section;
    }

    public function getFormColumns(): int
    {
        return $this->formColumns;
    }

    public function setFormColumns(int $formColumns): void
    {
        $this->formColumns = $formColumns;
    }

    private function snakeSection(): string
    {
        return (new UnicodeString($this->getSection()))->lower()->snake();
    }

    public function crudError(): Response
    {
        $this->addFlash(
            'warning',
            $this->getSection() . ' not found!'
        );

        return $this->redirectToRoute(
            'app_' . $this->snakeSection() . '_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}