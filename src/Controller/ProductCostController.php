<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\CategoryCostType;
use App\Form\ProductCostType;
use App\Form\SubcategoryCostType;
use App\Service\Crud\CrudHelper;
use App\Service\Crud\CrudUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
class ProductCostController extends AbstractController
{
    public const SECTION = 'Product';

    public function __construct(private readonly CrudUpdater $crudUpdater)
    {
    }

    #[Route('/{id}/cost', name: 'app_product_cost', methods: ['GET'])]
    public function cost(?Product $product, CrudHelper $crudHelper): Response
    {
        if (!$product) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        return $this->render('product/cost.html.twig', ['result' => $product]);
    }

    #[Route('/{id}/cost/edit', name: 'app_product_cost_edit', methods: ['GET', 'POST'])]
    public function costEdit(?Product $product, CrudHelper $crudHelper): Response
    {
        if (!$product) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        $form = $this->createForm(ProductCostType::class, $product, [
            'action' => $this->generateUrl('app_product_cost_edit', ['id' => $product->getId()]),
        ]);

        return $this->changeProductCost('Product Cost', $product, $product, $form);
    }

    #[Route('/{id}/cost/category/edit', name: 'app_product_cost_category_edit', methods: ['GET', 'POST'])]
    public function costCategoryEdit(?Product $product, CrudHelper $crudHelper): Response
    {
        if (!$product) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        $category = $product->getCategory();
        $form = $this->createForm(CategoryCostType::class, $category, [
            'action' => $this->generateUrl('app_product_cost_category_edit', ['id' => $product->getId()]),
        ]);

        return $this->changeProductCost('Category Cost', $product, $category, $form);
    }

    #[Route('/{id}/cost/subcategory/edit', name: 'app_product_cost_subcategory_edit', methods: ['GET', 'POST'])]
    public function costSubcategoryEdit(?Product $product, CrudHelper $crudHelper): Response
    {
        if (!$product) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        $subcategory = $product->getSubcategory();
        $form = $this->createForm(SubcategoryCostType::class, $subcategory, [
            'action' => $this->generateUrl('app_product_cost_subcategory_edit', ['id' => $product->getId()]),
        ]);

        return $this->changeProductCost('Subcategory Cost', $product, $subcategory, $form);
    }

    private function changeProductCost(
        string $section,
        Product $product,
        object $entity,
        FormInterface $form
    ): Response {
        $successLink = $this->generateUrl('app_product_cost', ['id' => $product->getId()]);
        $backLink = $this->generateUrl('app_product_cost', ['id' => $product->getId()]);

        $crudOptions = $this->crudUpdater->resetOptions()
            ->setSection($section)
            ->setEntity($entity)
            ->setForm($form)
            ->setSuccessLink($successLink)
            ->setBackLink($backLink);

        return $this->crudUpdater->build($crudOptions);
    }
}
