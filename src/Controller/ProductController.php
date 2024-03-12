<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\CrudHelper;
use App\Service\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    CONST string SECTION = 'Product';
    const int FORM_COLUMNS = 2;

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

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

        return $this->crudHelper->renderIndex(
            $productRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new Product(),
            ProductType::class,
        );
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(?Product $product): Response
    {
//        #[MapEntity(expr: 'repository.findFullProduct(id)')]
        return $this->crudHelper->renderShow($product);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?Product $product): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $product,
            ProductType::class,
        );
    }

    #[Route('/{id}/delete', name: 'app_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Product $product): Response
    {
        return $this->crudHelper->renderDeleteConfirm($product);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, ?Product $product): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $product,
        );
    }

    #[Route('/{id}/details', name: 'app_product_details', methods: ['GET'])]
    public function details(?Product $product): Response
    {
        return $this->render('product/_navigation.html.twig', [
            'section' => self::SECTION,
            'result' => $product,
        ]);
    }

    #[Route('/{id}/images', name: 'app_product_images', methods: ['GET'])]
    public function showProductImages(
        Request $request,
        ?Product $product,
        EntityManagerInterface $entityManager,
        UploadHelper $uploadHelper,
    ): Response
    {
        return $this->render('product/images.html.twig', [
            'result' => $product,
        ]);
    }
}