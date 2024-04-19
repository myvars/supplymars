<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    public const SECTION = 'Product';
    public const COLUMNS = 2;

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
    }

    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $sortOptions = ['id', 'name', 'cost', 'stock', 'sellPriceIncVat', 'isActive'];

        return $this->crudHelper->renderIndex(
            $productRepository,
            $sortOptions
        );
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        return $this->crudHelper->renderCreate(
            new Product(),
            ProductType::class,
            self::COLUMNS
        );
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(?Product $product): Response
    {
        //        #[MapEntity(expr: 'repository.findFullProduct(id)')]
        return $this->crudHelper->renderShow($product);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(?Product $product): Response
    {
        return $this->crudHelper->renderUpdate(
            $product,
            ProductType::class,
            self::COLUMNS
        );
    }

    #[Route('/{id}/delete/confirm', name: 'app_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Product $product): Response
    {
        return $this->crudHelper->renderDeleteConfirm($product);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(?Product $product): Response
    {
        return $this->crudHelper->renderDelete($product);
    }
}
