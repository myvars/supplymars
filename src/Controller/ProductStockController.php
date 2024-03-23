<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
class ProductStockController extends AbstractController
{
    public const string SECTION = 'Product';

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
    }

    #[Route('/{id}/stock', name: 'app_product_stock', methods: ['GET'])]
    public function stock(?Product $product, Request $request): Response
    {
        if (!$product) {
            return $this->crudHelper->renderShowEmpty(self::SECTION);
        }

        return $this->render('product/stock.html.twig', [
            'result' => $product,
        ]);
    }
}
