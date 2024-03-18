<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product/stock')]
class ProductStockController extends AbstractController
{
    #[Route('/{id}', name: 'app_product_stock', methods: ['GET'])]
    public function stock(?Product $product, Request $request): Response
    {
        return $this->render('product/stock.html.twig', [
            'result' => $product,
        ]);
    }
}
