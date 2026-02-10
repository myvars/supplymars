<?php

namespace App\Purchasing\UI\Http\Controller;

use App\Purchasing\Application\Command\PurchaseOrder\RewindPurchaseOrder;
use App\Purchasing\Application\Handler\PurchaseOrder\PurchaseOrderFilterHandler;
use App\Purchasing\Application\Handler\PurchaseOrder\RewindPurchaseOrderHandler;
use App\Purchasing\Application\Search\PurchaseOrderSearchCriteria;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Purchasing\UI\Http\Form\Mapper\PurchaseOrderFilterMapper;
use App\Purchasing\UI\Http\Form\Type\PurchaseOrderFilterType;
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
class PurchaseOrderController extends AbstractController
{
    private function model(): FlowModel
    {
        return FlowModel::create('purchasing', 'purchase_order');
    }

    #[Route(path: '/purchase/order/', name: 'app_purchasing_purchase_order_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        PurchaseOrderRepository $repository,
        #[MapQueryString] PurchaseOrderSearchCriteria $criteria = new PurchaseOrderSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch($this->model()),
        );
    }

    #[Route(path: '/purchase/order/search/filter', name: 'app_purchasing_purchase_order_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        PurchaseOrderFilterMapper $mapper,
        PurchaseOrderFilterHandler $handler,
        FormFlow $flow,
        #[MapQueryString] PurchaseOrderSearchCriteria $criteria = new PurchaseOrderSearchCriteria(),
    ): Response {
        return $flow->form(
            request: $request,
            formType: PurchaseOrderFilterType::class,
            data: $criteria,
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forFilter($this->model()),
        );
    }

    #[Route(path: '/purchase/order/{id}', name: 'app_purchasing_purchase_order_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] PurchaseOrder $purchaseOrder): Response
    {
        return $this->render('/purchasing/purchase_order/show.html.twig', ['result' => $purchaseOrder]);
    }

    #[Route(path: '/purchase/order/{id}/rewind/confirm', name: 'app_purchasing_purchase_order_rewind_confirm', methods: ['GET'])]
    public function rewindConfirm(
        #[ValueResolver('public_id')] PurchaseOrder $purchaseOrder,
        DeleteFlow $flow,
    ): Response {
        return $flow->deleteConfirm(
            entity: $purchaseOrder,
            context: FlowContext::forDelete($this->model())
                ->template('purchasing/purchase_order/rewind.html.twig'),
        );
    }

    #[Route(path: '/purchase/order/{id}/rewind', name: 'app_purchasing_purchase_order_rewind', methods: ['POST'])]
    public function rewind(
        Request $request,
        #[ValueResolver('public_id')] PurchaseOrder $purchaseOrder,
        RewindPurchaseOrderHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new RewindPurchaseOrder($purchaseOrder->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete($this->model())
                ->successRoute('app_purchasing_purchase_order_show', ['id' => $purchaseOrder->getPublicId()->value()]),
        );
    }
}
