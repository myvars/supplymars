<?php

namespace App\Controller;

use App\DTO\CreateOrderDto;
use App\Entity\CustomerOrder;
use App\Form\CreateOrderType;
use App\Repository\CustomerOrderRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudHelper;
use App\Service\Crud\CrudIndexer;
use App\Service\Crud\CrudUpdater;
use App\Service\Crud\CrudReader;
use App\Service\Order\CancelOrder;
use App\Service\Order\CreateOrder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
class OrderController extends AbstractController
{
    public const SECTION = 'Order';

    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(CustomerOrderRepository $repository, CrudIndexer $crudIndexer): Response
    {
        $sortOptions = ['id', 'createdAt', 'customer.fullName', 'totalPriceIncVat', 'status'];
        $crudOptions = $crudIndexer->createOptions(self::SECTION, $repository, $sortOptions)
            ->setSortDefault('id')
            ->setSortDirectionDefault('DESC');

        return $crudIndexer->build($crudOptions);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(
        CrudCreator $crudCreator,
        CreateOrder $crudAction,
        CreateOrderDto $createOrderDto
    ): Response {
        $form = $this->createForm(CreateOrderType::class, $createOrderDto, [
            'action' => $this->generateUrl('app_order_new'),
        ]);
        $crudOptions = $crudCreator->resetOptions()
            ->setSection(self::SECTION)
            ->setEntity($createOrderDto)
            ->setForm($form)
            ->setSuccessLink('app_order_index')
            ->setCrudAction($crudAction);

        return $crudCreator->build($crudOptions);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(?CustomerOrder $customerOrder, CrudReader $crudReader): Response
    {
        return $crudReader->read(self::SECTION, $customerOrder);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(?CustomerOrder $customerOrder, CrudUpdater $crudUpdater): Response
    {
        return $crudUpdater->update(self::SECTION, $customerOrder, CustomerOrder::class);
    }

    #[Route('/{id}/delete/confirm', name: 'app_order_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(?CustomerOrder $customerOrder, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->deleteConfirm(self::SECTION, $customerOrder);
    }

    #[Route('/{id}/delete', name: 'app_order_delete', methods: ['POST'])]
    public function delete(?CustomerOrder $customerOrder, CrudDeleter $crudDeleter): Response
    {
        return $crudDeleter->delete(self::SECTION, $customerOrder);
    }

    #[Route('/{id}/cancel', name: 'app_order_cancel', methods: ['GET'])]
    public function cancel(?CustomerOrder $customerOrder, CancelOrder $action, CrudHelper $crudHelper): Response
    {
        if (!$customerOrder) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        try {
            $action->cancel($customerOrder);
            $this->addFlash('success', 'Order cancelled successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Order cannot be cancelled');
        }

        return $crudHelper->redirectToLink(
            $this->generateUrl('app_order_show', ['id' => $customerOrder->getId()])
        );
    }
}
