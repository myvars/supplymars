<?php

namespace App\Pricing\UI\Http\Controller;

use App\Catalog\Domain\Model\Product\Product;
use App\Pricing\Application\Handler\UpdateCategoryCostHandler;
use App\Pricing\Application\Handler\UpdateProductCostHandler;
use App\Pricing\Application\Handler\UpdateSubcategoryCostHandler;
use App\Pricing\UI\Http\Form\Mapper\UpdateCategoryCostMapper;
use App\Pricing\UI\Http\Form\Mapper\UpdateProductCostMapper;
use App\Pricing\UI\Http\Form\Mapper\UpdateSubcategoryCostMapper;
use App\Pricing\UI\Http\Form\Model\CategoryCostForm;
use App\Pricing\UI\Http\Form\Model\ProductCostForm;
use App\Pricing\UI\Http\Form\Model\SubcategoryCostForm;
use App\Pricing\UI\Http\Form\Type\CategoryCostType;
use App\Pricing\UI\Http\Form\Type\ProductCostType;
use App\Pricing\UI\Http\Form\Type\SubcategoryCostType;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PricingController extends AbstractController
{
    public const string MODEL = 'pricing';

    #[Route(path: '/pricing/{id}/stock', name: 'app_pricing_stock', methods: ['GET'])]
    public function showStock(#[ValueResolver('public_id')] Product $product): Response
    {
        return $this->render('/pricing/stock.html.twig', ['result' => $product]);
    }

    #[Route(path: '/pricing/{id}/cost', name: 'app_pricing_cost', methods: ['GET'])]
    public function showCost(#[ValueResolver('public_id')] Product $product): Response
    {
        return $this->render('/pricing/cost.html.twig', ['result' => $product]);
    }

    #[Route(
        path: '/pricing/{id}/cost/product/edit',
        name: 'app_pricing_product_cost_edit',
        methods: ['GET', 'POST']
    )]
    public function productCostEdit(
        Request $request,
        #[ValueResolver('public_id')] Product $product,
        UpdateProductCostMapper $mapper,
        UpdateProductCostHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: ProductCostType::class,
            data: ProductCostForm::fromEntity($product),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)
                ->successRoute('app_pricing_cost', ['id' => $product->getPublicId()->value()]),
        );
    }

    #[Route(
        path: '/pricing/{id}/cost/category/edit',
        name: 'app_pricing_category_cost_edit',
        methods: ['GET', 'POST']
    )]
    public function categoryCostEdit(
        Request $request,
        #[ValueResolver('public_id')] Product $product,
        UpdateCategoryCostMapper $mapper,
        UpdateCategoryCostHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: CategoryCostType::class,
            data: CategoryCostForm::fromEntity($product->getCategory()),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)
                ->successRoute('app_pricing_cost', ['id' => $product->getPublicId()->value()]),
        );
    }

    #[Route(
        path: '/pricing/{id}/cost/subcategory/edit',
        name: 'app_pricing_subcategory_cost_edit',
        methods: ['GET', 'POST']
    )]
    public function subcategoryCostEdit(
        Request $request,
        #[ValueResolver('public_id')] Product $product,
        UpdateSubcategoryCostMapper $mapper,
        UpdateSubcategoryCostHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: SubcategoryCostType::class,
            data: SubcategoryCostForm::fromEntity($product->getSubcategory()),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)
                ->successRoute('app_pricing_cost', ['id' => $product->getPublicId()->value()]),
        );
    }
}
