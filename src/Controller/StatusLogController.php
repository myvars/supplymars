<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderItem;
use App\Service\OrderProcessing\StatusLogUtility;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class StatusLogController extends AbstractController
{
    #[Route(path: '/status/log/order/{id}', name: 'app_order_status_log', methods: ['GET'])]
    public function orderStatusLog(
        CustomerOrder $customerOrder,
        StatusLogUtility $statusLogUtility,
    ): Response {
        return $this->render('status_log/log.html.twig', [
            'section' => 'Customer Order',
            'result' => $customerOrder,
            'statusLog' => $statusLogUtility->forCustomerOrder($customerOrder),
        ]);
    }

    #[Route(path: '/status/log/order/item/{id}', name: 'app_order_item_status_log', methods: ['GET'])]
    public function orderItemStatusLog(
        CustomerOrderItem $customerOrderItem,
        StatusLogUtility $statusLogUtility,
    ): Response {
        return $this->render('status_log/log.html.twig', [
            'section' => 'Customer Order Item',
            'result' => $customerOrderItem,
            'statusLog' => $statusLogUtility->forCustomerOrderItem($customerOrderItem),
        ]);
    }

    #[Route(path: '/status/log/purchase/order/{id}', name: 'app_purchase_order_status_log', methods: ['GET'])]
    public function poStatusLog(
        PurchaseOrder $purchaseOrder,
        StatusLogUtility $statusLogUtility,
    ): Response {
        return $this->render('status_log/log.html.twig', [
            'section' => 'Purchase Order',
            'colourScheme' => $purchaseOrder->getSupplier()->getColourScheme(),
            'result' => $purchaseOrder,
            'statusLog' => $statusLogUtility->forPurchaseOrder($purchaseOrder),
        ]);
    }

    #[Route(
        path: '/status/log/purchase/order/item/{id}',
        name: 'app_purchase_order_item_status_log',
        methods: ['GET'])
    ]
    public function poItemStatusLog(
        PurchaseOrderItem $purchaseOrderItem,
        StatusLogUtility $statusLogUtility,
    ): Response {
        return $this->render('status_log/log.html.twig', [
            'section' => 'Purchase Order Item',
            'colourScheme' => $purchaseOrderItem->getPurchaseOrder()->getSupplier()->getColourScheme(),
            'result' => $purchaseOrderItem,
            'statusLog' => $statusLogUtility->forPurchaseOrderItem($purchaseOrderItem),
        ]);
    }
}
