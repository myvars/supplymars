<?php

namespace App\Controller;

use App\Entity\Subcategory;
use App\Form\SubcategoryType;
use App\Repository\SubcategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/subcategory')]
class SubcategoryController extends AbstractController
{
    CONST string SECTION = 'Subcategory';

    #[Route('/', name: 'app_subcategory_index', methods: ['GET'])]
    public function index(
        SubcategoryRepository $subcategoryRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'name', 'category.name', 'markup', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($subcategoryRepository->findBySearchQueryBuilder($query, $sort, $sortDirection)),
            $page,
            $limit
        );

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'index',
            'results' => $pager,
        ]);
    }

    #[Route('/new', name: 'app_subcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $subcategory = new Subcategory();
        $form = $this->createForm(SubcategoryType::class, $subcategory, [
            'action' => $this->generateUrl('app_subcategory_new')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($subcategory);
            $entityManager->flush();

            $this->addFlash('success', 'New '.self::SECTION.' added!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_subcategory_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'new',
            'result' => $subcategory,
            'form' => $form,
            'formColumns' => 2
        ]);
    }

    #[Route('/{id}', name: 'app_subcategory_show', methods: ['GET'])]
    public function show(Subcategory $subcategory): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'show',
            'result' => $subcategory,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Subcategory $subcategory, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SubcategoryType::class, $subcategory, [
            'action' => $this->generateUrl('app_subcategory_edit', ['id' => $subcategory->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' updated!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_subcategory_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'edit',
            'result' => $subcategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_subcategory_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(Subcategory $subcategory): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'delete',
            'result' => $subcategory,
        ]);
    }

    #[Route('/{id}', name: 'app_subcategory_delete', methods: ['POST'])]
    public function delete(Request $request, Subcategory $subcategory, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subcategory->getId(), $request->request->get('_token'))) {
            $entityManager->remove($subcategory);
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' deleted!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }
        }

        return $this->redirectToRoute('app_subcategory_index', [], Response::HTTP_SEE_OTHER);
    }
}
