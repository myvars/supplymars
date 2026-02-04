<?php

namespace App\Order\UI\Http\Controller;

use App\Order\Application\Command\CancelOrderItem;
use App\Order\Application\Handler\CancelOrderItemHandler;
use App\Order\Application\Handler\CreateOrderItemHandler;
use App\Order\Application\Handler\UpdateOrderItemHandler;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\UI\Http\Form\Mapper\CreateOrderItemMapper;
use App\Order\UI\Http\Form\Mapper\UpdateOrderItemMapper;
use App\Order\UI\Http\Form\Model\OrderItemForm;
use App\Order\UI\Http\Form\Model\UpdateOrderItemForm;
use App\Order\UI\Http\Form\Type\OrderItemType;
use App\Order\UI\Http\Form\Type\UpdateOrderItemType;
use App\Purchasing\Application\Command\PurchaseOrderItem\CreatePurchaseOrderItem;
use App\Purchasing\Application\Handler\PurchaseOrderItem\CreatePurchaseOrderItemHandler;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Shared\UI\Http\FormFlow\CommandFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class OrderItemController extends AbstractController
{
    public const string MODEL = 'Order Item';

    #[Route(path: '/order/', name: 'app_order_item_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_order_index');
    }

    #[Route(path: '/order/{id}/item/new', name: 'app_order_item_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        #[ValueResolver('public_id')] CustomerOrder $order,
        CreateOrderItemMapper $mapper,
        CreateOrderItemHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: OrderItemType::class,
            data: OrderItemForm::fromEntity($order),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate(self::MODEL)
                ->successRoute('app_order_show', ['id' => $order->getPublicId()->value()]),
        );
    }

    #[Route(path: '/order/item/{id}/edit', name: 'app_order_item_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[ValueResolver('public_id')] CustomerOrderItem $orderItem,
        UpdateOrderItemMapper $mapper,
        UpdateOrderItemHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: UpdateOrderItemType::class,
            data: UpdateOrderItemForm::fromEntity($orderItem),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forUpdate(self::MODEL)
                ->template('/order/update_item.html.twig')
                ->successRoute('app_order_show', [
                    'id' => $orderItem->getCustomerOrder()->getPublicId()->value(),
                ]),
        );
    }

    #[Route(path: '/order/item/{id}/cancel', name: 'app_order_item_cancel', methods: ['GET'])]
    public function cancel(
        Request $request,
        #[ValueResolver('public_id')] CustomerOrderItem $orderItem,
        CancelOrderItemHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new CancelOrderItem($orderItem->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_order_show', [
                'id' => $orderItem->getCustomerOrder()->getPublicId()->value(),
            ]),
        );
    }

    #[Route(
        path: '/order/item/{id}/supplier/product/{supplierProductId}/po/add',
        name: 'app_purchase_order_item_add',
        methods: ['GET']
    )]
    public function addToPurchaseOrder(
        Request $request,
        #[ValueResolver('public_id')] CustomerOrderItem $orderItem,
        string $supplierProductId,
        CreatePurchaseOrderItemHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new CreatePurchaseOrderItem(
                $orderItem->getPublicId(),
                SupplierProductPublicId::fromString($supplierProductId)
            ),
            handler: $handler,
            context: FlowContext::forSuccess('app_order_show', [
                'id' => $orderItem->getCustomerOrder()->getPublicId()->value(),
            ]),
        );
    }

    #[Route(path: '/order/item/{id}', name: 'app_order_item_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] CustomerOrderItem $orderItem): Response
    {
        return $this->render('/order/show_item.html.twig', ['result' => $orderItem]);
    }
}
