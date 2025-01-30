<?php

namespace App\Controller;

use App\DTO\CreateOrderItemDto;
use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Form\CreateOrderItemType;
use App\Form\EditOrderItemType;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudHandler;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudUpdater;
use App\Service\Order\CancelOrderItem;
use App\Service\Order\CreateOrderItem;
use App\Service\Order\EditOrderItem;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_ADMIN')]
class OrderItemController extends AbstractController
{
    public const SECTION = 'Order Item';

    #[Route('/', name: 'app_order_item_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/item/{id}', name: 'app_order_item_show', methods: ['GET'])]
    public function show(
        CustomerOrderItem $customerOrderItem,
        CrudReader $handler
    ): Response {
        return $handler->read(self::SECTION, $customerOrderItem);
    }

    #[Route('/{id}/item/new', name: 'app_order_item_new', methods: ['GET', 'POST'])]
    public function new(
        CustomerOrder $customerOrder,
        CrudCreator $handler,
        CreateOrderItem $crudAction,
    ): Response {
        $createOrderItemDto = CreateOrderItemDto::fromEntity($customerOrder);
        $form = $this->createForm(CreateOrderItemType::class, $createOrderItemDto, [
            'action' => $this->generateUrl('app_order_item_new', ['id' => $customerOrder->getId()]),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setSection(self::SECTION)
                ->setEntity($createOrderItemDto)
                ->setForm($form)
                ->setCrudAction($crudAction)
                ->setSuccessLink(
                    $this->generateUrl('app_order_show', ['id' => $customerOrder->getId()])
                )
        );
    }

    #[Route('/item/{id}/edit', name: 'app_order_item_edit', methods: ['GET', 'POST'])]
    public function edit(
        CustomerOrderItem $orderItem,
        CrudUpdater $handler,
        EditOrderItem $crudAction,
    ): Response {
        $editOrderItemDto = EditOrderItemDto::fromEntity($orderItem);
        $form = $this->createForm(EditOrderItemType::class, $editOrderItemDto, [
            'action' => $this->generateUrl('app_order_item_edit', ['id' => $editOrderItemDto->getId()]),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setSection(self::SECTION)
                ->setEntity($editOrderItemDto)
                ->setForm($form)
                ->setCrudAction($crudAction)
                ->setSuccessLink(
                    $this->generateUrl('app_order_show', ['id' => $orderItem->getCustomerOrder()->getId()])
                )
        );
    }

    #[Route('/item/{id}/supplier/product/{supplierProductId}/po/add',
        name: 'app_purchase_order_item_add',
        methods: ['GET']
    )]
    public function addToPurchaseOrder(
        CustomerOrderItem $customerOrderItem,
        int $supplierProductId,
        CrudHandler $handler,
        CreatePurchaseOrderItem $action,
    ): Response {
        return $handler->build(
            $handler->setDefaults()
                ->setEntity($customerOrderItem)
                ->setCrudAction($action)
                ->setCrudActionContext(['supplierProductId' => $supplierProductId])
                ->setSuccessFlash('PO item added')
                ->setErrorFlash('PO item could not be added')
                ->setSuccessLink(
                    $this->generateUrl('app_order_show', [
                        'id' => $customerOrderItem->getCustomerOrder()->getId()
                    ])
                )
        );
    }

    #[Route('/item/{id}/cancel', name: 'app_order_item_cancel', methods: ['GET'])]
    public function cancel(
        CustomerOrderItem $customerOrderItem,
        CrudHandler $handler,
        CancelOrderItem $action,
    ): Response {
        return $handler->build(
            $handler->setDefaults()
                ->setEntity($customerOrderItem)
                ->setCrudAction($action)
                ->setSuccessFlash('Order item cancelled')
                ->setErrorFlash('Order item cannot be cancelled')
                ->setSuccessLink(
                    $this->generateUrl('app_order_show', ['id' => $customerOrderItem->getCustomerOrder()->getId()])
                )
        );
    }
}