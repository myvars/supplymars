<?php

namespace App\Controller;

use App\DTO\PurchaseOrderItemEditDto;
use App\Entity\PurchaseOrderItem;
use App\Form\PurchaseOrderItemEditType;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudUpdater;
use App\Strategy\CrudPOItemEditStrategy;
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
        CrudPOItemEditStrategy $crudStrategy,
    ): Response
    {
        $editPOItemDto = PurchaseOrderItemEditDto::createFromEntity($purchaseOrderItem);
        $form = $this->createForm(PurchaseOrderItemEditType::class, $editPOItemDto, [
            'action' => $this->generateUrl('app_purchase_order_item_edit', ['id' => $purchaseOrderItem->getId()]),
        ]);

        $successResponse = $this->redirectToRoute(
            'app_purchase_order_show', ['id' => $purchaseOrderItem->getPurchaseOrder()->getId()],
            Response::HTTP_SEE_OTHER
        );

        $crudOptions = $crudUpdater->resetOptions()
            ->setSection(self::SECTION)
            ->setEntity($editPOItemDto)
            ->setForm($form)
            ->setSuccessResponse($successResponse)
            ->setCrudStrategy($crudStrategy)
            ->setCrudStrategyContext(['purchaseOrderItem' => $purchaseOrderItem])
        ;

        return $crudUpdater->build($crudOptions);
    }
}
