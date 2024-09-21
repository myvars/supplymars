<?php

namespace App\Controller;

use App\DTO\CreateOrderItemDto;
use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Form\CreateOrderItemType;
use App\Form\EditOrderItemType;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudHelper;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudUpdater;
use App\Service\Order\CreateOrderItem;
use App\Service\Order\EditOrderItem;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_USER')]
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
        CreateOrderItem $crudAction,
    ): Response {
        if (!$customerOrder instanceof CustomerOrder) {

            return $crudCreator->crudHelper->showEmpty('Order');
        }

        $createOrderItemDto = CreateOrderItemDto::fromEntity($customerOrder);

        $form = $this->createForm(CreateOrderItemType::class, $createOrderItemDto, [
            'action' => $this->generateUrl('app_order_item_new', ['id' => $customerOrder->getId()]),
        ]);
        $successLink = $this->generateUrl('app_order_show', ['id' => $customerOrder->getId()]);

        $crudOptions = $crudCreator->resetOptions()
            ->setSection(self::SECTION)
            ->setEntity($createOrderItemDto)
            ->setForm($form)
            ->setSuccessLink($successLink)
            ->setCrudAction($crudAction);

        return $crudCreator->build($crudOptions);
    }

    #[Route('/item/{id}/edit', name: 'app_order_item_edit', methods: ['GET', 'POST'])]
    public function edit(
        ?CustomerOrderItem $customerOrderItem,
        CrudUpdater $crudUpdater,
        EditOrderItem $crudAction,
    ): Response {
        if (!$customerOrderItem instanceof CustomerOrderItem) {
            return $crudUpdater->crudHelper->showEmpty(self::SECTION);
        }

        $editOrderItemDto = EditOrderItemDto::fromEntity($customerOrderItem);

        $form = $this->createForm(EditOrderItemType::class, $editOrderItemDto, [
            'action' => $this->generateUrl('app_order_item_edit', ['id' => $editOrderItemDto->getId()]),
        ]);
        $successLink = $this->generateUrl(
            'app_order_show', ['id' => $customerOrderItem->getCustomerOrder()->getId()]
        );

        $crudOptions = $crudUpdater->resetOptions()
            ->setSection(self::SECTION)
            ->setEntity($editOrderItemDto)
            ->setForm($form)
            ->setSuccessLink($successLink)
            ->setCrudAction($crudAction)
            ->setAllowDelete(false);

        return $crudUpdater->build($crudOptions);
    }

    #[Route('/item/{id}/supplier/product/{supplierProductId}/po/add',
        name: 'app_purchase_order_item_add',
        methods: ['GET']
    )]
    public function addToPurchaseOrder(
        ?CustomerOrderItem $customerOrderItem,
        int $supplierProductId,
        CrudHelper $crudHelper,
        CreatePurchaseOrderItem $createPurchaseOrderItem
    ): Response {
        try {
            $supplierProduct = null;
            foreach($customerOrderItem->getProduct()->getSupplierProducts() as $supplierProduct) {
                if ($supplierProduct->getId() === $supplierProductId) {

                    break;
                }
            }

            $createPurchaseOrderItem->fromOrder($customerOrderItem, $supplierProduct);
        } catch (\Exception) {
            $this->addFlash('danger', 'PO item could not be added');
        }

        $this->addFlash('success', 'PO item added');

        return $crudHelper->redirectToLink(
            $this->generateUrl('app_order_show', ['id' => $customerOrderItem->getCustomerOrder()->getId()])
        );
    }
}