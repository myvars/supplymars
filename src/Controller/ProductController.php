<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/product')]
class ProductController extends AbstractController
{
    CONST string SECTION = 'Product';

    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'name', 'cost', 'sellPrice', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        try {
            $pager = Pagerfanta::createForCurrentPageWithMaxPerPage(
                new QueryAdapter($productRepository->findBySearchQueryBuilder($query, $sort, $sortDirection)),
                $page,
                $limit
            );
        } catch (OutOfRangeCurrentPageException $e) {
            return $this->redirectToRoute('app_product_index', [
                'page' => 1,
                'limit' => $limit,
                'sort' => '$sort',
                'sortDirection' => $sortDirection,
                'query' => $query,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'index',
            'results' => $pager,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product, [
            'action' => $this->generateUrl('app_product_new')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'New '.self::SECTION.' added!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'new',
            'result' => $product,
            'form' => $form,
            'formColumns' => 2
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(
//        #[MapEntity(expr: 'repository.findFullProduct(id)')]
        Product $product
    ): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'show',
            'result' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product, [
            'action' => $this->generateUrl('app_product_edit', ['id' => $product->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' updated!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'edit',
            'result' => $product,
            'form' => $form,
            'formColumns' => 2
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(Product $product): Response
    {
        return $this->render('crud/crud.html.twig', [
            'section' => self::SECTION,
            'template' => 'delete',
            'result' => $product,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', self::SECTION.' deleted!');

            if ($request->headers->has('turbo-frame')) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

                return $this->renderBlock('common/turboStreamRefresh.html.twig', 'stream_success');
            }
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
