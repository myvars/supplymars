<?php

namespace App\Controller\Action;

use App\DTO\OrderItemEditDto;
use App\Entity\CustomerOrderItem;
use App\Form\OrderItemEditType;
use App\Service\Crud\CrudHelper;
use App\Service\Order\OrderItemUpdater;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order/item/{id}/edit_test', name: 'app_order_item_edit', methods: ['GET', 'POST'])]
class OrderItemUpdateAction extends AbstractController
{
    public function __construct(
        private readonly CrudHelper $crudHelper,
        private readonly OrderItemUpdater $orderItemUpdater
    ) {
    }

    public function __invoke(?CustomerOrderItem $customerOrderItem, Request $request): Response
    {
        if (!$customerOrderItem) {
            return $this->crudHelper->showEmpty('Order Item');
        }

        $orderItemEditDto = OrderItemEditDto::createFromEntity($customerOrderItem);
        $form = $this->createForm(OrderItemEditType::class, $orderItemEditDto, [
            'action' => $this->generateUrl('app_order_item_edit', ['id' => $customerOrderItem->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->orderItemUpdater->updateFromDto($orderItemEditDto);
                $this->addFlash('success', 'Order Item updated!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Can not update Order Item!');
            }

            return  $this->crudHelper->redirectToRoute(
                'app_order_show', ['id' => $customerOrderItem->getCustomerOrder()->getId()],
            );
        }

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => 'Order Item',
            'template' => 'edit',
            'result' => $orderItemEditDto,
            'form' => $form,
            'backLink' => $this->generateUrl(
                'app_order_show', ['id' => $customerOrderItem->getCustomerOrder()->getId()]
            ),
            'allowDelete' => true,
            'formColumns' => 1
        ]);
    }
}