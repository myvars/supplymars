<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudIndexer;
use App\Service\Crud\CrudUpdater;
use App\Service\Crud\CrudReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    public const SECTION = 'Product';

    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $repository, CrudIndexer $crudIndexer): Response
    {
        $sortOptions = ['id', 'name', 'cost', 'stock', 'sellPriceIncVat', 'isActive'];

        return $crudIndexer->index(self::SECTION, $repository, $sortOptions);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(CrudCreator $crudCreator): Response
    {
        return $crudCreator->create(self::SECTION, new Product(), ProductType::class);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(?Product $product, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $product);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(?Product $product, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $product, ProductType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_product_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?Product $product, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $product);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(?Product $product, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->delete(self::SECTION, $product);
    }
}