<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ProductStockController extends AbstractController
{
    public const string SECTION = 'Product';

    #[Route(path: '/product/{id}/stock', name: 'app_product_stock', methods: ['GET'])]
    public function stock(Product $product): Response
    {
        return $this->render('product/stock.html.twig', ['result' => $product]);
    }
}
