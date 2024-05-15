<?php

namespace App\Controller;

use App\DTO\OrderItemCreateDto;
use App\DTO\OrderItemEditDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Form\OrderItemCreateType;
use App\Form\OrderItemEditType;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudHelper;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudUpdater;
use App\Strategy\CrudOrderItemCreateStrategy;
use App\Strategy\CrudOrderItemEditStrategy;
use App\Strategy\CrudPOItemCreateStrategy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
class OrderItemController extends AbstractController
{
    public const SECTION = 'Order Item';

    #[Route('/', name: 'app_order_item_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_order_index');
    }

    #[Route('/item/{id}', name: 'app_order_item_show', methods: ['GET'])]
    public function show(?CustomerOrderItem $customerOrderItem, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $customerOrderItem);
    }

    #[Route('/{id}/item/new', name: 'app_order_item_new', methods: ['GET', 'POST'])]
    public function new(
        ?CustomerOrder $customerOrder,
        CrudCreator $crudCreator,
        CrudOrderItemCreateStrategy $crudStrategy,
    ): Response {
        $createOrderItemDto = OrderItemCreateDto::createFromEntity($customerOrder);
        $form = $this->createForm(OrderItemCreateType::class, $createOrderItemDto, [
            'action' => $this->generateUrl('app_order_item_new', ['id' => $customerOrder->getId()]),
        ]);
        $successResponse = $this->redirectToRoute(
            'app_order_show', ['id' => $customerOrder->getId()],
            Response::HTTP_SEE_OTHER
        );
        $crudOptions = $crudCreator->resetOptions()
            ->setSection(self::SECTION)
            ->setEntity($createOrderItemDto)
            ->setForm($form)
            ->setSuccessResponse($successResponse)
            ->setCrudStrategy($crudStrategy)
            ->setCrudStrategyContext(['customerOrder' => $customerOrder])
        ;

        return $crudCreator->build($crudOptions);
    }

    #[Route('/item/{id}/edit', name: 'app_order_item_edit', methods: ['GET', 'POST'])]
    public function edit(
        ?CustomerOrderItem $customerOrderItem,
        CrudUpdater $crudUpdater,
        CrudOrderItemEditStrategy $crudStrategy,
    ): Response
    {
        if (!$customerOrderItem) {
            return $crudUpdater->crudHelper->showEmpty(self::SECTION);
        }

        $orderItemEditDto = OrderItemEditDto::createFromEntity($customerOrderItem);
        $form = $this->createForm(OrderItemEditType::class, $orderItemEditDto, [
            'action' => $this->generateUrl('app_order_item_edit', ['id' => $customerOrderItem->getId()]),
        ]);
        $successResponse = $this->redirectToRoute(
            'app_order_show', ['id' => $customerOrderItem->getCustomerOrder()->getId()],
            Response::HTTP_SEE_OTHER
        );
        $crudOptions = $crudUpdater->resetOptions()
            ->setSection(self::SECTION)
            ->setEntity($orderItemEditDto)
            ->setForm($form)
            ->setSuccessResponse($successResponse)
            ->setCrudStrategy($crudStrategy)
            ->setCrudStrategyContext(['customerOrderItem' => $customerOrderItem])
            ->setAllowDelete(true)
        ;

        return $crudUpdater->build($crudOptions);
    }

    #[Route('/item/{id}/delete/confirm', name: 'app_order_item_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?CustomerOrderItem $customerOrderItem, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $customerOrderItem);
    }

    #[Route('/item/{id}/delete', name: 'app_order_item_delete', methods: ['POST'])]
    public function delete(?CustomerOrderItem $customerOrderItem, CrudDeleter $crudDeleter): Response
    {
        $successResponse =  $this->redirectToRoute(
            'app_order_show', ['id' => $customerOrderItem->getCustomerOrder()->getId()],
            Response::HTTP_SEE_OTHER
        );
        $crudOptions = $crudDeleter->resetOptions()
            ->setSection(self::SECTION)
            ->setEntity($customerOrderItem)
            ->setSuccessResponse($successResponse)
        ;

        return $crudDeleter->build($crudOptions);
    }

    #[Route('/item/{id}/supplier/product/{supplierProductId}/po/add', name: 'app_purchase_order_item_add', methods: ['GET'])]
    public function addToPurchaseOrder(
        ?CustomerOrderItem $customerOrderItem,
        int $supplierProductId,
        CrudHelper $crudHelper,
        CrudPOItemCreateStrategy $crudStrategy
    ): Response {
        $product = $customerOrderItem->getProduct();
        foreach($product->getSupplierProducts() as $supplierProduct) {
            if ($supplierProduct->getId() === $supplierProductId) {
                break;
            }
        }
        try {
            $crudStrategy->create($customerOrderItem, ['supplierProductId' => $supplierProductId]);
        } catch (\Exception $e) {
            $this->addFlash(
                'danger',
                'PO item could not be added'
            );
        }

        $this->addFlash(
            'success',
            'PO item added'
        );

        return $crudHelper->streamRefresh();
    }
}