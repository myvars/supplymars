<?php

namespace App\Controller;

use App\DTO\SearchDto\OrderSearchDto;
use App\DTO\SearchDto\ProductSearchDto;
use App\DTO\SearchDto\PurchaseOrderSearchDto;
use App\DTO\SearchDto\SearchFilterInterface;
use App\DTO\SearchDto\SubcategorySearchDto;
use App\DTO\SearchDto\SupplierProductSearchDto;
use App\Form\SearchForm\OrderSearchFilterType;
use App\Form\SearchForm\ProductSearchFilterType;
use App\Form\SearchForm\PurchaseOrderSearchFilterType;
use App\Form\SearchForm\SubcategorySearchFilterType;
use App\Form\SearchForm\SupplierProductSearchFilterType;
use App\Service\Crud\CrudHandler;
use App\Service\Search\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/search/filter')]
#[IsGranted('ROLE_USER')]
class SearchFilterController extends AbstractController
{
    private const SEARCH_FILTER_TEMPLATE = 'search_filter/search_filter.html.twig';

    public function __construct(
        private readonly CrudHandler $crudHandler,
        private readonly SearchFilter $action,
    ) {
    }

    #[Route('/order', name: 'app_order_search_filter', methods: ['GET', 'POST'])]
    public function orderSearchFilter(
        Request $request,
        #[MapQueryString] OrderSearchDto $dto = new OrderSearchDto()
    ): Response {
        $successLink = $this->generateUrl('app_order_index');

        return $this->buildSearchFilter($request, $dto, OrderSearchFilterType::class, $successLink);
    }

    #[Route('/purchase/order', name: 'app_purchase_order_search_filter', methods: ['GET', 'POST'])]
    public function purchaseOrderSearchFilter(
        Request $request,
        #[MapQueryString] PurchaseOrderSearchDto $dto = new PurchaseOrderSearchDto()
    ): Response {
        $successLink = $this->generateUrl('app_purchase_order_index');

        return $this->buildSearchFilter($request, $dto, PurchaseOrderSearchFilterType::class, $successLink);
    }

    #[Route('/product', name: 'app_product_search_filter', methods: ['GET', 'POST'])]
    public function productSearchFilter(
        Request $request,
        #[MapQueryString] ProductSearchDto $dto = new ProductSearchDto()
    ): Response {
        $successLink = $this->generateUrl('app_product_index');

        return $this->buildSearchFilter($request, $dto, ProductSearchFilterType::class, $successLink);
    }

    #[Route('/subcategory', name: 'app_subcategory_search_filter', methods: ['GET', 'POST'])]
    public function subcategorySearchFilter(
        Request $request,
        #[MapQueryString] SubcategorySearchDto $dto = new SubcategorySearchDto()
    ): Response {
        $successLink = $this->generateUrl('app_subcategory_index');

        return $this->buildSearchFilter($request, $dto, SubcategorySearchFilterType::class, $successLink);
    }

    #[Route('/supplier/product', name: 'app_supplier_product_search_filter', methods: ['GET', 'POST'])]
    public function supplierProductSearchFilter(
        Request $request,
        #[MapQueryString] SupplierProductSearchDto $dto = new SupplierProductSearchDto()
    ): Response {
        $successLink = $this->generateUrl('app_supplier_product_index');

        return $this->buildSearchFilter($request, $dto, SupplierProductSearchFilterType::class, $successLink);
    }

    public function buildSearchFilter(
        Request $request,
        SearchFilterInterface $dto,
        string $formType,
        string $successLink
    ): Response {
        $dto->setQueryString($request->getQueryString());

        $form = $this->createForm($formType, $dto, [
            'action' => $request->getPathInfo() . ($request->getQueryString() ? '?' . $request->getQueryString() : '')
        ]);

        return $this->crudHandler->build($this->crudHandler->getOptions()
            ->setTemplate(self::SEARCH_FILTER_TEMPLATE)
            ->setForm($form)
            ->setEntity($dto)
            ->setCrudAction($this->action)
            ->setSuccessLink($successLink)
        );
    }
}
