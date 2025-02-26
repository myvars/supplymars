<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\CategoryCostType;
use App\Form\ProductCostType;
use App\Form\SubcategoryCostType;
use App\Service\Crud\CrudUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ProductCostController extends AbstractController
{
    public const string SECTION = 'Product';

    #[Route(path: '/product/{id}/cost', name: 'app_product_cost', methods: ['GET'])]
    public function cost(Product $product): Response
    {
        return $this->render('product/cost.html.twig', ['result' => $product]);
    }

    #[Route(path: '/product/{id}/cost/edit', name: 'app_product_cost_edit', methods: ['GET', 'POST'])]
    public function costEdit(
        Product $product,
        CrudUpdater $handler,
    ): Response {
        $form = $this->createForm(ProductCostType::class, $product, [
            'action' => $this->generateUrl('app_product_cost_edit', ['id' => $product->getId()]),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setSection('Product Cost')
                ->setEntity($product)
                ->setForm($form)
                ->setSuccessFlash('Product Cost updated!')
                ->setErrorFlash('Can not update Product Cost!')
                ->setSuccessLink(
                    $this->generateUrl('app_product_cost', ['id' => $product->getId()])
                )
        );
    }

    #[Route(
        path: '/product/{id}/cost/category/edit',
        name: 'app_product_cost_category_edit',
        methods: ['GET', 'POST']
    )]
    public function costCategoryEdit(
        Product $product,
        CrudUpdater $handler,
    ): Response {
        $category = $product->getCategory();
        $form = $this->createForm(CategoryCostType::class, $category, [
            'action' => $this->generateUrl('app_product_cost_category_edit', ['id' => $product->getId()]),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setSection('Category Cost')
                ->setEntity($category)
                ->setForm($form)
                ->setSuccessFlash('Category Cost updated!')
                ->setErrorFlash('Can not update Category Cost!')
                ->setSuccessLink(
                    $this->generateUrl('app_product_cost', ['id' => $product->getId()])
                )
        );
    }

    #[Route(
        path: '/product/{id}/cost/subcategory/edit',
        name: 'app_product_cost_subcategory_edit',
        methods: ['GET', 'POST']
    )]
    public function costSubcategoryEdit(
        Product $product,
        CrudUpdater $handler,
    ): Response {
        $subcategory = $product->getSubcategory();
        $form = $this->createForm(SubcategoryCostType::class, $subcategory, [
            'action' => $this->generateUrl('app_product_cost_subcategory_edit', ['id' => $product->getId()]),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setSection('Subcategory Cost')
                ->setEntity($subcategory)
                ->setForm($form)
                ->setSuccessFlash('Subcategory Cost updated!')
                ->setErrorFlash('Can not update Subcategory Cost!')
                ->setSuccessLink(
                    $this->generateUrl('app_product_cost', ['id' => $product->getId()])
                )
        );
    }
}
