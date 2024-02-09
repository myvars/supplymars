<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        EntityRepository $entityRepository,
        array $validSorts = ['id'],
        int $page = 1,
        int $limit = 10,
        string $sort = 'id',
        string $sortDirection = 'ASC',
        string $query = null,
    ): Response
    {
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter(
                $entityRepository->findBySearchQueryBuilder($query, $sort, $sortDirection)
            ),
            $page,
            $limit
        );

        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => 'index',
            'results' => $pager,
        ]);
    }

    public function renderCreate(
        Request $request,
        object $entity,
        string $formType
    ): Response
    {
        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl('app_'.$this->snakeSection().'_new')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'New '.$this->getSection().' added!'
            );

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

    public function renderShow(object $entity): Response
    {
        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => 'show',
            'result' => $entity,
        ]);
    }

    public function renderUpdate(
        Request $request,
        object $entity,
        string $formType,
    ): Response
    {
        $form = $this->createForm($formType, $entity, [
            'action' => $this->generateUrl('app_'.$this->snakeSection().'_edit', ['id' => $entity->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $this->getSection().' updated!'
            );

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

    public function renderDeleteConfirm(object $entity): Response
    {
        return $this->render(self::CRUD_BASE_TEMPLATE, [
            'section' => $this->getSection(),
            'template' => 'delete',
            'result' => $entity,
        ]);
    }

    public function renderDelete(
        Request $request,
        object $entity,
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token')))
        {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                $this->getSection().' deleted!'
            );

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
}