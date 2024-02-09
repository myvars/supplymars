<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/category')]
class CategoryController extends AbstractController
{
    CONST string SECTION = 'Category';

    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'name', 'markup', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($categoryRepository->findBySearchQueryBuilder($query, $sort, $sortDirection)),
            $page,
            $limit
        );

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'index',
            'results' => $pager,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category, [
            'action' => $this->generateUrl('app_category_new')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'New '.self::SECTION.' added!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'new',
            'result' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'show',
            'result' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category, [
            'action' => $this->generateUrl('app_category_edit', ['id' => $category->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' updated!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'edit',
            'result' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_category_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(Category $category): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'delete',
            'result' => $category,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' deleted!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
