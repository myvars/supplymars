<?php

namespace App\Controller;

use App\Entity\ProductImage;
use App\Form\ProductImageType;
use App\Repository\ProductImageRepository;
use App\Service\CrudHelper;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product-image')]
class ProductImageController extends AbstractController
{
    CONST string SECTION = 'Product Image';
    const int FORM_COLUMNS = 1;

    public function __construct(
        private readonly CrudHelper $crudHelper,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        $this->crudHelper->setSection(self::SECTION);
        $this->crudHelper->setFormColumns(self::FORM_COLUMNS);
    }

    #[Route('/', name: 'app_product_image_index', methods: ['GET'])]
    public function index(
        ProductImageRepository $productImageRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $limit = 10,
        #[MapQueryParameter] string $sort = 'id',
        #[MapQueryParameter] string $sortDirection = 'ASC',
        #[MapQueryParameter] string $query = null,
    ): Response
    {
        $validSorts = ['id', 'product', 'imageName', 'isActive'];
        $sort = in_array($sort, $validSorts) ? $sort : 'id';

        return $this->crudHelper->renderIndex(
            $productImageRepository->findBySearchQueryBuilder($query, $sort, $sortDirection),
            $page,
            $limit,
            $sort,
            $sortDirection,
            $query,
        );
    }

    #[Route('/new', name: 'app_product_image_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->crudHelper->renderCreate(
            $request,
            new ProductImage(),
            ProductImageType::class,
        );
    }

    #[Route('/{id}', name: 'app_product_image_show', methods: ['GET'])]
    public function show(?ProductImage $productImage): Response
    {
//        #[MapEntity(expr: 'repository.findFullProduct(id)')]
        return $this->crudHelper->renderShow($productImage);
    }

    #[Route('/{id}/edit', name: 'app_product_image_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ?ProductImage $productImage): Response
    {
        return $this->crudHelper->renderUpdate(
            $request,
            $productImage,
            ProductImageType::class,
        );
    }

    #[Route('/{id}/delete', name: 'app_product_image_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?ProductImage $productImage): Response
    {
        return $this->crudHelper->renderDeleteConfirm($productImage);
    }

    #[Route('/{id}', name: 'app_product_image_delete', methods: ['POST'])]
    public function delete(Request $request, ?ProductImage $productImage): Response
    {
        return $this->crudHelper->renderDelete(
            $request,
            $productImage,
        );
    }
}
