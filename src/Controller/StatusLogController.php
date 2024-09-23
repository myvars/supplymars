<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Service\StatusLogUtility;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/status/log')]
#[IsGranted('ROLE_USER')]
class StatusLogController extends AbstractController
{
    #[Route('/order/{id}', name: 'app_order_status_log', methods: ['GET'])]
    public function orderStatusLog(?CustomerOrder $customerOrder, StatusLogUtility $statusLogUtility): Response
    {
        return $this->render('status_log/log.html.twig', [
            'section' => 'Customer Order',
            'result' => $customerOrder,
            'statusLog' => $statusLogUtility->forCustomerOrder($customerOrder)
        ]);
    }

    #[Route('/order/item/{id}', name: 'app_order_item_status_log', methods: ['GET'])]
    public function orderItemStatusLog(?CustomerOrderItem $customerOrderItem, StatusLogUtility $statusLogUtility): Response
    {
        return $this->render('status_log/log.html.twig', [
            'section' => 'Customer Order Item',
            'result' => $customerOrderItem,
            'statusLog' => $statusLogUtility->forCustomerOrderItem($customerOrderItem),
        ]);
    }

    #[Route('/purchase/order/{id}', name: 'app_purchase_order_status_log', methods: ['GET'])]
    public function poStatusLog(?PurchaseOrder $purchaseOrder, StatusLogUtility $statusLogUtility): Response
    {
        return $this->render('status_log/log.html.twig', [
            'section' => 'Purchase Order',
            'result' => $purchaseOrder,
            'statusLog' => $statusLogUtility->forPurchaseOrder($purchaseOrder),
        ]);
    }

    #[Route('/purchase/order/item/{id}', name: 'app_purchase_order_item_status_log', methods: ['GET'])]
    public function poItemStatusLog(?PurchaseOrderItem $purchaseOrderItem, StatusLogUtility $statusLogUtility): Response
    {
        return $this->render('status_log/log.html.twig', [
            'section' => 'Purchase Order Item',
            'result' => $purchaseOrderItem,
            'statusLog' => $statusLogUtility->forPurchaseOrderItem($purchaseOrderItem),
        ]);
    }
}
