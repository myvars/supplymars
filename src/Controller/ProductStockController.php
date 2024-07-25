<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\Crud\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
class ProductStockController extends AbstractController
{
    public const SECTION = 'Product';

    #[Route('/{id}/stock', name: 'app_product_stock', methods: ['GET'])]
    public function stock(?Product $product, CrudHelper $crudHelper): Response
    {
        if (!$product) {

            return $crudHelper->showEmpty(self::SECTION);
        }

        return $this->render('product/stock.html.twig', ['result' => $product]);
    }
}