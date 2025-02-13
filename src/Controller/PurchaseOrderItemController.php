<?php

namespace App\Controller;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\DTO\EditPurchaseOrderItemDto;
use App\Entity\PurchaseOrderItem;
use App\Form\ChangePurchaseOrderItemStatusType;
use App\Form\EditPurchaseOrderItemType;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudUpdater;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\PurchaseOrder\EditPurchaseOrderItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/purchase/order/item')]
#[IsGranted('ROLE_ADMIN')]
class PurchaseOrderItemController extends AbstractController
{
    public const string SECTION = 'Purchase Order Item';

    #[Route('/', name: 'app_purchase_order_item_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_purchase_order_index');
    }

    #[Route('/{id}', name: 'app_purchase_order_item_show', methods: ['GET'])]
    public function show(
        PurchaseOrderItem $purchaseOrderItem,
        CrudReader $handler
    ): Response {
        return $handler->read(self::SECTION, $purchaseOrderItem);
    }

    #[Route('/{id}/edit', name: 'app_purchase_order_item_edit', methods: ['GET', 'POST'])]
    public function edit(
        PurchaseOrderItem $purchaseOrderItem,
        CrudUpdater $handler,
        EditPurchaseOrderItem $action,
    ): Response
    {
        $editPOItemDto = EditPurchaseOrderItemDto::fromEntity($purchaseOrderItem);

        return $handler->build(
            $handler->setup(self::SECTION, $editPOItemDto, EditPurchaseOrderItemType::class)
                ->setCrudAction($action)
                ->setAllowDelete(false)
                ->setSuccessLink(
                    $this->generateUrl('app_purchase_order_show', [
                        'id' => $purchaseOrderItem->getPurchaseOrder()->getId()
                    ])
                )
                ->setSafetyLink(
                    $this->generateUrl('app_order_show', [
                        'id' => $purchaseOrderItem->getPurchaseOrder()->getCustomerOrder()->getId()
                    ])
                )
        );
    }

    #[Route('/{id}/edit/status', name: 'app_purchase_order_item_status_edit', methods: ['GET', 'POST'])]
    public function editStatus(
        PurchaseOrderItem $purchaseOrderItem,
        CrudUpdater $handler,
        ChangePurchaseOrderItemStatus $action
    ): Response {
        $changePurchaseOrderItemStatusDto = ChangePurchaseOrderItemStatusDto::fromEntity($purchaseOrderItem);
        $form = $this->createForm(ChangePurchaseOrderItemStatusType::class, $changePurchaseOrderItemStatusDto, [
            'action' => $this->generateUrl(
                'app_purchase_order_item_status_edit', ['id' => $purchaseOrderItem->getId()]
            ),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setSection(self::SECTION)
                ->setEntity($changePurchaseOrderItemStatusDto)
                ->setForm($form)
                ->setCrudAction($action)
                ->setSuccessFlash('PO Item status updated!')
                ->setErrorFlash('Can not update PO Item status!')
                ->setSuccessLink(
                    $this->generateUrl('app_purchase_order_show', [
                        'id' => $purchaseOrderItem->getPurchaseOrder()->getId()
                    ])
                )
        );
    }
}
