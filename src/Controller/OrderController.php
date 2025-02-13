<?php

namespace App\Controller;

use App\DTO\CreateOrderDto;
use App\DTO\SearchDto\OrderSearchDto;
use App\Entity\CustomerOrder;
use App\Form\CreateOrderType;
use App\Form\SearchForm\OrderSearchFilterType;
use App\Repository\CustomerOrderRepository;
use App\Service\Crud\CrudCreator;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudHandler;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudSearcher;
use App\Service\Crud\CrudUpdater;
use App\Service\Order\CancelOrder;
use App\Service\Order\CreateOrder;
use App\Service\Order\LockOrder;
use App\Service\Order\ProcessOrder;
use App\Service\Search\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_ADMIN')]
class OrderController extends AbstractController
{
    public const string SECTION = 'Order';

    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $handler,
        CustomerOrderRepository $repository,
        #[MapQueryString] OrderSearchDto $dto = new OrderSearchDto()
    ): Response {
        return $handler->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route('/search/filter', name: 'app_order_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        CrudUpdater $handler,
        SearchFilter $action,
        #[MapQueryString] OrderSearchDto $dto = new OrderSearchDto()
    ): Response {
        $dto->setQueryString($request->getQueryString());
        $form = $this->createForm(OrderSearchFilterType::class, $dto, [
            'action' => $this->generateUrl('app_order_search_filter', $request->query->all()),
        ]);

        return $handler->build(
            $handler->setDefaults()
                ->setTemplate($dto::TEMPLATE)
                ->setForm($form)
                ->setEntity($dto)
                ->setCrudAction($action)
                ->setSuccessLink(
                    $this->generateUrl('app_order_index')
                )
        );
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(
        CrudCreator $handler,
        CreateOrder $action,
        CreateOrderDto $createOrderDto
    ): Response {
        return $handler->build(
            $handler->setup(self::SECTION, $createOrderDto, CreateOrderType::class)
                ->setCrudAction($action)
        );
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(
        CustomerOrder $customerOrder,
        CrudReader $handler
    ): Response {
        return $handler->read(self::SECTION, $customerOrder);
    }

    #[Route('/{id}/cancel/confirm', name: 'app_order_cancel_confirm', methods: ['GET'])]
    public function cancelConfirm(
        CustomerOrder $customerOrder,
        CrudReader $handler
    ): Response {
        return $handler->build(
            $handler->setDefaults()
                ->setEntity($customerOrder)
                ->setTemplate('order/cancel.html.twig')
        );
    }

    #[Route('/{id}/cancel', name: 'app_order_cancel', methods: ['POST'])]
    public function cancel(
        CustomerOrder $customerOrder,
        CrudDeleter $handler,
        CancelOrder $action
    ): Response {
        return $handler->build(
            $handler->setup(self::SECTION, $customerOrder)
                ->setCrudAction($action)
                ->setSuccessFlash('Order cancelled')
                ->setErrorFlash('Order cannot be cancelled')
                ->setSuccessLink(
                    $this->generateUrl('app_order_show', ['id' => $customerOrder->getId()])
                )
        );
    }

    #[Route('/{id}/process', name: 'app_order_process', methods: ['GET'])]
    public function process(
        CustomerOrder $customerOrder,
        CrudHandler $handler,
        ProcessOrder $action
    ): Response {
        return $handler->build(
            $handler->setDefaults()
                ->setEntity($customerOrder)
                ->setCrudAction($action)
                ->setSuccessFlash('Order processed')
                ->setErrorFlash('Order cannot be processed')
                ->setSuccessLink(
                    $this->generateUrl('app_order_show', ['id' => $customerOrder->getId()])
                )
        );
    }

    #[Route('/{id}/lock/toggle', name: 'app_order_lock_toggle_status', methods: ['GET'])]
    public function toggleStatus(
        CustomerOrder $customerOrder,
        CrudHandler $handler,
        LockOrder $action
    ): Response{
        return $handler->build(
            $handler->setDefaults()
                ->setEntity($customerOrder)
                ->setCrudAction($action)
                ->setSuccessLink(
                    $this->generateUrl('app_order_show', ['id' => $customerOrder->getId()])
                )
        );
    }
}
