<?php

namespace App\Purchasing\UI\Http\Controller;

use App\Purchasing\Application\Handler\PurchaseOrderItem\UpdatePurchaseOrderItemQuantityHandler;
use App\Purchasing\Application\Handler\PurchaseOrderItem\UpdatePurchaseOrderItemStatusHandler;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\UI\Http\Form\Mapper\UpdatePurchaseOrderItemQuantityMapper;
use App\Purchasing\UI\Http\Form\Mapper\UpdatePurchaseOrderItemStatusMapper;
use App\Purchasing\UI\Http\Form\Model\PurchaseOrderItemQuantityForm;
use App\Purchasing\UI\Http\Form\Model\PurchaseOrderItemStatusForm;
use App\Purchasing\UI\Http\Form\Type\PurchaseOrderItemQuantityType;
use App\Purchasing\UI\Http\Form\Type\PurchaseOrderItemStatusType;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PurchaseOrderItemController extends AbstractController
{
    public const string MODEL = 'purchasing/purchase order item';

    #[Route(path: '/purchase/order/item/', name: 'app_purchasing_purchase_order_item_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_purchasing_purchase_order_index');
    }

    #[Route(path: '/purchase/order/item/{id}', name: 'app_purchasing_purchase_order_item_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] PurchaseOrderItem $purchaseOrderItem): Response
    {
        return $this->render('/purchasing/purchase_order_item/show.html.twig', [
            'result' => $purchaseOrderItem,
        ]);
    }

    #[Route(
        path: '/purchase/order/item/{id}/edit',
        name: 'app_purchasing_purchase_order_item_edit',
        methods: ['GET', 'POST'])]
    public function editQuantity(
        Request $request,
        #[ValueResolver('public_id')] PurchaseOrderItem $purchaseOrderItem,
        UpdatePurchaseOrderItemQuantityMapper $mapper,
        UpdatePurchaseOrderItemQuantityHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: PurchaseOrderItemQuantityType::class,
            data: PurchaseOrderItemQuantityForm::fromEntity($purchaseOrderItem),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)
                ->successRoute('app_order_show', [
                    'id' => $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder()->getPublicId()->value(),
                ]),
        );
    }

    #[Route(
        path: '/purchase/order/item/{id}/edit/status',
        name: 'app_purchasing_purchase_order_item_status_edit',
        methods: ['GET', 'POST']
    )]
    public function editStatus(
        Request $request,
        #[ValueResolver('public_id')] PurchaseOrderItem $purchaseOrderItem,
        UpdatePurchaseOrderItemStatusMapper $mapper,
        UpdatePurchaseOrderItemStatusHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: PurchaseOrderItemStatusType::class,
            data: PurchaseOrderItemStatusForm::fromEntity($purchaseOrderItem),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)
                ->successRoute('app_purchasing_purchase_order_show', [
                    'id' => $purchaseOrderItem->getPurchaseOrder()->getPublicId()->value(),
                ]),
        );
    }
}
