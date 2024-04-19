<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\CategoryCostType;
use App\Form\ProductCostType;
use App\Form\SubcategoryCostType;
use App\Service\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
class ProductCostController extends AbstractController
{
    public const SECTION = 'Product';

    public function __construct(private readonly CrudHelper $crudHelper)
    {
        $this->crudHelper->setSection(self::SECTION);
    }

    #[Route('/{id}/cost', name: 'app_product_cost', methods: ['GET'])]
    public function cost(?Product $product): Response
    {
        if (!$product) {
            return $this->crudHelper->renderShowEmpty(self::SECTION);
        }

        return $this->render('product/cost.html.twig', [
            'result' => $product,
        ]);
    }

    #[Route('/{id}/cost/edit', name: 'app_product_cost_edit', methods: ['GET', 'POST'])]
    public function costEdit(?Product $product): Response
    {
        if (!$product) {
            return $this->crudHelper->renderShowEmpty(self::SECTION);
        }

        $form = $this->createForm(ProductCostType::class, $product, [
            'action' => $this->generateUrl(
                'app_product_cost_edit', [
                    'id' => $product->getId(),
                ]
            ),
        ]);

        return $this->renderCostUpdate(
            'Product Cost',
            $product,
            $product,
            $form
        );
    }

    #[Route('/{id}/cost/category/edit', name: 'app_product_cost_category_edit', methods: ['GET', 'POST'])]
    public function costCategoryEdit(?Product $product): Response
    {
        if (!$product) {
            return $this->crudHelper->renderShowEmpty(self::SECTION);
        }

        $category = $product->getCategory();
        $form = $this->createForm(CategoryCostType::class, $category, [
            'action' => $this->generateUrl(
                'app_product_cost_category_edit', [
                    'id' => $product->getId(),
                ]
            ),
        ]);

        return $this->renderCostUpdate(
            'Category Cost',
            $product,
            $category,
            $form
        );
    }

    #[Route('/{id}/cost/subcategory/edit', name: 'app_product_cost_subcategory_edit', methods: ['GET', 'POST'])]
    public function costSubcategoryEdit(?Product $product): Response
    {
        if (!$product) {
            return $this->crudHelper->renderShowEmpty(self::SECTION);
        }

        $subcategory = $product->getSubcategory();
        $form = $this->createForm(SubcategoryCostType::class, $subcategory, [
            'action' => $this->generateUrl(
                'app_product_cost_subcategory_edit', [
                    'id' => $product->getId(),
                ]
            ),
        ]);

        return $this->renderCostUpdate(
            'Subcategory Cost',
            $product,
            $subcategory,
            $form
        );
    }

    private function renderCostUpdate(
        string $section,
        Product $product,
        object $entity,
        FormInterface $form
    ): Response {
        $successResponse = $this->redirectToRoute(
            'app_product_cost', [
                'id' => $product->getId(),
            ],
            Response::HTTP_SEE_OTHER
        );

        return $this->crudHelper->renderCustomUpdate(
            $section,
            $entity,
            $form,
            $successResponse,
            $this->generateUrl('app_product_cost', [
                'id' => $product->getId(),
            ])
        );
    }
}
