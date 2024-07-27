<?php

namespace App\Controller\Action;

use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrderItem;
use App\Form\EditOrderItemType;
use App\Service\Crud\CrudHelper;
use App\Service\Order\EditOrderItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order/item/{id}/edit_test', name: 'app_order_item_edit', methods: ['GET', 'POST'])]
class EditOrderItemAction extends AbstractController
{
    public function __construct(
        private readonly CrudHelper $crudHelper,
        private readonly EditOrderItem $editOrderItem
    ) {
    }

    public function __invoke(?CustomerOrderItem $customerOrderItem, Request $request): Response
    {
        if (!$customerOrderItem) {
            return $this->crudHelper->showEmpty('Order Item');
        }

        $editOrderItemDto = EditOrderItemDto::fromEntity($customerOrderItem);

        $form = $this->createForm(EditOrderItemType::class, $editOrderItemDto, [
            'action' => $this->generateUrl('app_order_item_edit', ['id' => $editOrderItemDto->getId()]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->editOrderItem->handle($editOrderItemDto, null);
                $this->addFlash('success', 'Order Item updated!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Can not update Order Item!');
            }

            return  $this->crudHelper->redirectToLink(
                $this->generateUrl('app_order_show', ['id' => $customerOrderItem->getCustomerOrder()->getId()]),
            );
        }

        return $this->render($this->crudHelper::CRUD_BASE_TEMPLATE, [
            'section' => 'Order Item',
            'template' => 'edit',
            'result' => $editOrderItemDto,
            'form' => $form,
            'backLink' => $this->generateUrl(
                'app_order_show', ['id' => $customerOrderItem->getCustomerOrder()->getId()]
            ),
            'allowDelete' => true,
            'formColumns' => 1
        ]);
    }
}