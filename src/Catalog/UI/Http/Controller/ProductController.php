<?php

namespace App\Catalog\UI\Http\Controller;

use App\Catalog\Application\Command\Product\DeleteProduct;
use App\Catalog\Application\Handler\Product\CreateProductHandler;
use App\Catalog\Application\Handler\Product\DeleteProductHandler;
use App\Catalog\Application\Handler\Product\ProductFilterHandler;
use App\Catalog\Application\Handler\Product\UpdateProductHandler;
use App\Catalog\Application\Search\ProductSearchCriteria;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Catalog\UI\Http\Form\Mapper\CreateProductMapper;
use App\Catalog\UI\Http\Form\Mapper\ProductFilterMapper;
use App\Catalog\UI\Http\Form\Mapper\UpdateProductMapper;
use App\Catalog\UI\Http\Form\Model\ProductForm;
use App\Catalog\UI\Http\Form\Type\ProductFilterType;
use App\Catalog\UI\Http\Form\Type\ProductType;
use App\Review\Domain\Repository\ReviewRepository;
use App\Review\Domain\Repository\ReviewSummaryRepository;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    public const string MODEL = 'catalog/product';

    #[Route(path: '/product/', name: 'app_catalog_product_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        ProductRepository $repository,
        #[MapQueryString] ProductSearchCriteria $criteria = new ProductSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch(self::MODEL),
        );
    }

    #[Route(path: '/product/search/filter', name: 'app_catalog_product_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        ProductFilterMapper $mapper,
        ProductFilterHandler $handler,
        FormFlow $flow,
        #[MapQueryString] ProductSearchCriteria $criteria = new ProductSearchCriteria(),
    ): Response {
        return $flow->form(
            request: $request,
            formType: ProductFilterType::class,
            data: $criteria,
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forFilter(self::MODEL),
        );
    }

    #[Route(path: '/product/new', name: 'app_catalog_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateProductMapper $mapper,
        CreateProductHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: ProductType::class,
            data: new ProductForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate(self::MODEL),
        );
    }

    #[Route(path: '/product/{id}/edit', name: 'app_catalog_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] Product $product,
        UpdateProductMapper $mapper,
        UpdateProductHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: ProductType::class,
            data: ProductForm::fromEntity($product),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)->allowDelete(true),
        );
    }

    #[Route(path: '/product/{id}/delete/confirm', name: 'app_catalog_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        #[ValueResolver('public_id')] Product $product,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $product,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/product/{id}/delete', name: 'app_catalog_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] Product $product,
        DeleteProductHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeleteProduct($product->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/product/{id}', name: 'app_catalog_product_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] Product $product): Response
    {
        return $this->render('/catalog/product/show.html.twig', ['result' => $product]);
    }

    #[Route(path: '/product/{id}/sales', name: 'app_catalog_product_sales', methods: ['GET'])]
    public function sales(#[ValueResolver('public_id')] Product $product): Response
    {
        return $this->render('catalog/product/sales.html.twig', ['result' => $product]);
    }

    #[Route(path: '/product/{id}/reviews', name: 'app_catalog_product_reviews', methods: ['GET'])]
    public function reviews(
        #[ValueResolver('public_id')] Product $product,
        ReviewRepository $reviewRepository,
        ReviewSummaryRepository $summaryRepository,
    ): Response {
        return $this->render('catalog/product/reviews.html.twig', [
            'result' => $product,
            'summary' => $summaryRepository->findByProduct($product),
            'reviews' => $reviewRepository->findLatestPublishedForProduct($product, 5),
        ]);
    }
}
