<?php

namespace App\Controller;

use App\DTO\SearchDto\ProductSearchDto;
use App\Entity\Product;
use App\Form\ProductType;
use App\Form\SearchForm\ProductSearchFilterType;
use App\Repository\ProductRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudSearcher;
use App\Service\Crud\CrudUpdater;
use App\Service\Search\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    public const string SECTION = 'Product';

    #[Route(path: '/product/', name: 'app_product_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $handler,
        ProductRepository $repository,
        #[MapQueryString] ProductSearchDto $dto = new ProductSearchDto(),
    ): Response {
        return $handler->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route(path: '/product/search/filter', name: 'app_product_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        CrudUpdater $handler,
        SearchFilter $action,
        #[MapQueryString] ProductSearchDto $dto = new ProductSearchDto(),
    ): Response {
        $dto->setQueryString($request->getQueryString());
        $form = $this->createForm(ProductSearchFilterType::class, $dto, [
            'action' => $this->generateUrl('app_product_search_filter', $request->query->all()),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setTemplate($dto::TEMPLATE)
                ->setForm($form)
                ->setEntity($dto)
                ->setCrudAction($action)
                ->setSuccessLink(
                    $this->generateUrl('app_product_index')
                )
        );
    }

    #[Route(path: '/product/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $handler): Response
    {
        return $handler->create(self::SECTION, new Product(), ProductType::class);
    }

    #[Route(path: '/product/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(
        ?Product $product,
        CrudReader $handler,
    ): Response {
        return $handler->read(self::SECTION, $product);
    }

    #[Route(path: '/product/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Product $product,
        CrudUpdater $handler,
    ): Response {
        return $handler->update(self::SECTION, $product, ProductType::class);
    }

    #[Route(path: '/product/{id}/delete/confirm', name: 'app_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        ?Product $product,
        CrudDeleter $handler,
    ): Response {
        return $handler->deleteConfirm(self::SECTION, $product);
    }

    #[Route(path: '/product/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Product $product,
        CrudDeleter $handler,
    ): Response {
        return $handler->delete(self::SECTION, $product);
    }

    #[Route(path: '/product/{id}/sales', name: 'app_product_sales', methods: ['GET'])]
    public function sales(Product $product): Response
    {
        return $this->render('product/sales.html.twig', ['result' => $product]);
    }
}
