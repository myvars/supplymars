<?php

namespace App\Purchasing\UI\Http\Controller;

use App\Purchasing\Application\Command\Supplier\DeleteSupplier;
use App\Purchasing\Application\Handler\Supplier\CreateSupplierHandler;
use App\Purchasing\Application\Handler\Supplier\DeleteSupplierHandler;
use App\Purchasing\Application\Handler\Supplier\UpdateSupplierHandler;
use App\Purchasing\Application\Search\SupplierSearchCriteria;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Purchasing\UI\Http\Form\Mapper\CreateSupplierMapper;
use App\Purchasing\UI\Http\Form\Mapper\UpdateSupplierMapper;
use App\Purchasing\UI\Http\Form\Model\SupplierForm;
use App\Purchasing\UI\Http\Form\Type\SupplierType;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SupplierController extends AbstractController
{
    public const string MODEL = 'purchasing/supplier';

    #[Route(path: '/supplier/', name: 'app_purchasing_supplier_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        SupplierRepository $repository,
        #[MapQueryString] SupplierSearchCriteria $criteria = new SupplierSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch(self::MODEL),
        );
    }

    #[Route(path: '/supplier/new', name: 'app_purchasing_supplier_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateSupplierMapper $mapper,
        CreateSupplierHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: SupplierType::class,
            data: new SupplierForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate(self::MODEL),
        );
    }

    #[Route(path: '/supplier/{id}/edit', name: 'app_purchasing_supplier_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] Supplier $supplier,
        UpdateSupplierMapper $mapper,
        UpdateSupplierHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: SupplierType::class,
            data: SupplierForm::fromEntity($supplier),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)->allowDelete(true),
        );
    }

    #[Route(path: '/supplier/{id}/delete/confirm', name: 'app_purchasing_supplier_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(
        #[ValueResolver('public_id')] Supplier $supplier,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $supplier,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/supplier/{id}/delete', name: 'app_purchasing_supplier_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] Supplier $supplier,
        DeleteSupplierHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new DeleteSupplier($supplier->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete(self::MODEL),
        );
    }

    #[Route(path: '/supplier/{id}', name: 'app_purchasing_supplier_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] Supplier $supplier): Response
    {
        return $this->render('/purchasing/supplier/show.html.twig', ['result' => $supplier]);
    }
}
