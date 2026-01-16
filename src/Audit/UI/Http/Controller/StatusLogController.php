<?php

namespace App\Audit\UI\Http\Controller;

use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Shared\Domain\Event\DomainEventType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class StatusLogController extends AbstractController
{
    public function __construct(private readonly StatusChangeLogRepository $logs)
    {
    }

    #[Route(path: '/status/log/order/{id}', name: 'app_order_status_log', methods: ['GET'])]
    public function orderStatusLog(
        #[ValueResolver('public_id')] CustomerOrder $order,
    ): Response {
        return $this->render('audit/log.html.twig', [
            'entity' => 'Customer Order',
            'result' => $order,
            'statusLog' => $this->logs->findByEvent(
                DomainEventType::ORDER_STATUS_CHANGED,
                $order->getId()
            ),
        ]);
    }

    #[Route(path: '/status/log/order/item/{id}', name: 'app_order_item_status_log', methods: ['GET'])]
    public function orderItemStatusLog(
        #[ValueResolver('public_id')] CustomerOrderItem $orderItem,
    ): Response {
        return $this->render('audit/log.html.twig', [
            'entity' => 'Customer Order Item',
            'result' => $orderItem,
            'statusLog' => $this->logs->find(
                DomainEventType::ORDER_ITEM_STATUS_CHANGED,
                $orderItem->getId()
            ),
        ]);
    }

    #[Route(path: '/status/log/purchase/order/{id}', name: 'app_purchase_order_status_log', methods: ['GET'])]
    public function poStatusLog(
        #[ValueResolver('public_id')] PurchaseOrder $purchaseOrder,
    ): Response {
        return $this->render('audit/log.html.twig', [
            'entity' => 'Purchase Order',
            'colourScheme' => $purchaseOrder->getSupplier()->getColourScheme(),
            'result' => $purchaseOrder,
            'statusLog' => $this->logs->find(
                DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
                $purchaseOrder->getId()
            ),
        ]);
    }

    #[Route(
        path: '/status/log/purchase/order/item/{id}',
        name: 'app_purchase_order_item_status_log',
        methods: ['GET'])
    ]
    public function poItemStatusLog(
        #[ValueResolver('public_id')] PurchaseOrderItem $purchaseOrderItem,
    ): Response {
        return $this->render('audit/log.html.twig', [
            'entity' => 'Purchase Order Item',
            'colourScheme' => $purchaseOrderItem->getPurchaseOrder()->getSupplier()->getColourScheme(),
            'result' => $purchaseOrderItem,
            'statusLog' => $this->logs->find(
                DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
                $purchaseOrderItem->getId()
            ),
        ]);
    }
}
