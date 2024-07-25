<?php

namespace App\Controller;

use App\DTO\EditPurchaseOrderItemDto;
use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\PurchaseOrderItem;
use App\Form\EditPurchaseOrderItemType;
use App\Form\ChangePurchaseOrderItemStatusType;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudUpdater;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\PurchaseOrder\EditPurchaseOrderItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/purchase/order/item')]
class PurchaseOrderItemController extends AbstractController
{
    public const SECTION = 'Purchase Order Item';

    #[Route('/', name: 'app_purchase_order_item_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_purchase_order_index');
    }

    #[Route('/{id}', name: 'app_purchase_order_item_show', methods: ['GET'])]
    public function show(?PurchaseOrderItem $purchaseOrderItem, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $purchaseOrderItem);
    }

    #[Route('/{id}/edit', name: 'app_purchase_order_item_edit', methods: ['GET', 'POST'])]
    public function edit(
        ?PurchaseOrderItem $purchaseOrderItem,
        CrudUpdater $crudUpdater,
        EditPurchaseOrderItem $crudAction,
    ): Response
    {
        if (!$purchaseOrderItem) {

            return $crudUpdater->crudHelper->showEmpty(self::SECTION);
        }

        $editPOItemDto = EditPurchaseOrderItemDto::fromEntity($purchaseOrderItem);

        $form = $this->createForm(EditPurchaseOrderItemType::class, $editPOItemDto, [
            'action' => $this->generateUrl(
                'app_purchase_order_item_edit',
                ['id' => $purchaseOrderItem->getId()]
            ),
        ]);
        $successLink = $this->generateUrl(
            'app_purchase_order_show', ['id' => $purchaseOrderItem->getPurchaseOrder()->getId()]
        );

        $crudOptions = $crudUpdater->resetOptions()
            ->setSection(self::SECTION)
            ->setEntity($editPOItemDto)
            ->setForm($form)
            ->setSuccessLink($successLink)
            ->setCrudAction($crudAction);

        return $crudUpdater->build($crudOptions);
    }

    #[Route('/{id}/edit/status', name: 'app_purchase_order_item_status_edit', methods: ['GET', 'POST'])]
    public function editStatus(
        ?PurchaseOrderItem $purchaseOrderItem,
        CrudUpdater $crudUpdater,
        ChangePurchaseOrderItemStatus $crudAction
    ): Response {
        if (!$purchaseOrderItem) {

            return $crudUpdater->crudHelper->showEmpty(self::SECTION);
        }

        $changePurchaseOrderItemStatusDto = ChangePurchaseOrderItemStatusDto::fromEntity($purchaseOrderItem);

        $form = $this->createForm(ChangePurchaseOrderItemStatusType::class, $changePurchaseOrderItemStatusDto, [
            'action' => $this->generateUrl(
                'app_purchase_order_item_status_edit', ['id' => $purchaseOrderItem->getId()]
            ),
        ]);
        $successLink = $this->generateUrl(
            'app_purchase_order_show', ['id' => $purchaseOrderItem->getPurchaseOrder()->getId()]
        );

        $crudOptions = $crudUpdater->resetOptions()
            ->setSection(self::SECTION)
            ->setEntity($changePurchaseOrderItemStatusDto)
            ->setForm($form)
            ->setSuccessLink($successLink)
            ->setCrudAction($crudAction)
            ->setAllowDelete(false);

        return $crudUpdater->build($crudOptions);
    }
}
