<?php

namespace App\Purchasing\UI\Http\Controller;

use App\Purchasing\Application\Command\SupplierProduct\DeleteSupplierProduct;
use App\Purchasing\Application\Command\SupplierProduct\MapSupplierProduct;
use App\Purchasing\Application\Command\SupplierProduct\RemoveSupplierProduct;
use App\Purchasing\Application\Command\SupplierProduct\ToggleSupplierProductStatus;
use App\Purchasing\Application\Handler\SupplierProduct\CreateSupplierProductHandler;
use App\Purchasing\Application\Handler\SupplierProduct\DeleteSupplierProductHandler;
use App\Purchasing\Application\Handler\SupplierProduct\MapSupplierProductHandler;
use App\Purchasing\Application\Handler\SupplierProduct\RemoveSupplierProductHandler;
use App\Purchasing\Application\Handler\SupplierProduct\SupplierProductFilterHandler;
use App\Purchasing\Application\Handler\SupplierProduct\ToggleSupplierProductStatusHandler;
use App\Purchasing\Application\Handler\SupplierProduct\UpdateSupplierProductHandler;
use App\Purchasing\Application\Search\SupplierProductSearchCriteria;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Purchasing\UI\Http\Form\Mapper\CreateSupplierProductMapper;
use App\Purchasing\UI\Http\Form\Mapper\SupplierProductFilterMapper;
use App\Purchasing\UI\Http\Form\Mapper\UpdateSupplierProductMapper;
use App\Purchasing\UI\Http\Form\Model\SupplierProductForm;
use App\Purchasing\UI\Http\Form\Type\SupplierProductFilterType;
use App\Purchasing\UI\Http\Form\Type\SupplierProductType;
use App\Shared\UI\Http\FormFlow\CommandFlow;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SupplierProductController extends AbstractController
{
    private function model(): FlowModel
    {
        return FlowModel::create('purchasing', 'supplier_product');
    }

    #[Route(path: '/supplier-product/', name: 'app_purchasing_supplier_product_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        SupplierProductRepository $repository,
        #[MapQueryString] SupplierProductSearchCriteria $criteria = new SupplierProductSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch($this->model()),
        );
    }

    #[Route(
        path: '/supplier-product/search/filter',
        name: 'app_purchasing_supplier_product_search_filter',
        methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        SupplierProductFilterMapper $mapper,
        SupplierProductFilterHandler $handler,
        FormFlow $flow,
        #[MapQueryString] SupplierProductSearchCriteria $criteria = new SupplierProductSearchCriteria(),
    ): Response {
        return $flow->form(
            request: $request,
            formType: SupplierProductFilterType::class,
            data: $criteria,
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forFilter($this->model()),
        );
    }

    #[Route(path: '/supplier-product/new', name: 'app_purchasing_supplier_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateSupplierProductMapper $mapper,
        CreateSupplierProductHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: SupplierProductType::class,
            data: new SupplierProductForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate($this->model()),
        );
    }

    #[Route(path: '/supplier-product/{id}/edit', name: 'app_purchasing_supplier_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] SupplierProduct $supplierProduct,
        UpdateSupplierProductMapper $mapper,
        UpdateSupplierProductHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: SupplierProductType::class,
            data: SupplierProductForm::fromEntity($supplierProduct),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate($this->model())
                ->allowDelete(true)
                ->successRoute('app_purchasing_supplier_product_show', [
                    'id' => $supplierProduct->getPublicId()->value(),
                ])
        );
    }

    #[Route(
        path: '/supplier-product/{id}/delete/confirm',
        name: 'app_purchasing_supplier_product_delete_confirm',
        methods: ['GET']
    )]
    public function deleteConfirm(
        #[ValueResolver('public_id')] SupplierProduct $supplierProduct,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $supplierProduct,
            context: FlowContext::forDelete($this->model()),
        );
    }

    #[Route(path: '/supplier-product/{id}/delete', name: 'app_purchasing_supplier_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] SupplierProduct $supplierProduct,
        DeleteSupplierProductHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeleteSupplierProduct($supplierProduct->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete($this->model()),
        );
    }

    #[Route(path: '/supplier-product/{id}', name: 'app_purchasing_supplier_product_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] SupplierProduct $supplierProduct): Response
    {
        return $this->render('/purchasing/supplier_product/show.html.twig', ['result' => $supplierProduct]);
    }

    #[Route(
        path: '/supplier-product/{id}/remove/confirm',
        name: 'app_purchasing_supplier_product_remove_confirm',
        methods: ['GET']
    )]
    public function removeConfirm(
        #[ValueResolver('public_id')] SupplierProduct $supplierProduct,
    ): Response {
        return $this->render('/purchasing/supplier_product/remove.html.twig', ['result' => $supplierProduct]);
    }

    #[Route(path: '/supplier-product/{id}/remove', name: 'app_purchasing_supplier_product_remove', methods: ['POST'])]
    public function remove(
        Request $request,
        #[ValueResolver('public_id')] SupplierProduct $supplierProduct,
        RemoveSupplierProductHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new RemoveSupplierProduct($supplierProduct->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete($this->model())
            ->successRoute('app_pricing_stock', [
                'id' => $supplierProduct->getProduct()->getPublicId()->value(),
            ]),
        );
    }

    #[Route(
        path: '/supplier-product/{id}/status/toggle',
        name: 'app_purchasing_supplier_product_toggle_status',
        methods: ['GET']
    )]
    public function toggleStatus(
        Request $request,
        #[ValueResolver('public_id')] SupplierProduct $supplierProduct,
        ToggleSupplierProductStatusHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new ToggleSupplierProductStatus($supplierProduct->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_pricing_stock', [
                'id' => $supplierProduct->getProduct()?->getPublicId()->value(),
            ]),
        );
    }

    #[Route(path: '/supplier-product/{id}/map', name: 'app_purchasing_supplier_product_map', methods: ['GET'])]
    public function map(
        Request $request,
        #[ValueResolver('public_id')] SupplierProduct $supplierProduct,
        MapSupplierProductHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new MapSupplierProduct($supplierProduct->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_purchasing_supplier_product_show', [
                'id' => $supplierProduct->getPublicId()->value(),
            ]),
        );
    }
}
