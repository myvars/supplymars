<?php

namespace App\Controller;

use App\Entity\PurchaseOrder;
use App\Form\ChangePurchaseOrderItemStatusType;
use App\Repository\PurchaseOrderRepository;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudIndexer;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudUpdater;
use App\Service\StatusLogUtility;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/purchase/order')]
#[IsGranted('ROLE_USER')]
class PurchaseOrderController extends AbstractController
{
    public const SECTION = 'Purchase Order';

    #[Route('/', name: 'app_purchase_order_index', methods: ['GET'])]
    public function index(PurchaseOrderRepository $repository, CrudIndexer $crudIndexer): Response
    {
        $sortOptions = ['id', 'createdAt', 'customerOrder.id', 'totalPriceIncVat', 'status'];

        return $crudIndexer->index(self::SECTION, $repository, $sortOptions);
    }

    #[Route('/{id}', name: 'app_purchase_order_show', methods: ['GET'])]
    public function show(?PurchaseOrder $purchaseOrder, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $purchaseOrder);
    }

    #[Route('/{id}/edit', name: 'app_purchase_order_edit', methods: ['GET', 'POST'])]
    public function edit(?PurchaseOrder $purchaseOrder, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $purchaseOrder, ChangePurchaseOrderItemStatusType::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_purchase_order_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?PurchaseOrder $purchaseOrder, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $purchaseOrder);
    }

    #[Route('/{id}/delete', name: 'app_purchase_order_delete', methods: ['POST'])]
    public function delete(?PurchaseOrder $purchaseOrder, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->delete(self::SECTION, $purchaseOrder);
    }
}
