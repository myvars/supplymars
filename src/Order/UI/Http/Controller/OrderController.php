<?php

namespace App\Order\UI\Http\Controller;

use App\Order\Application\Command\AllocateOrder;
use App\Order\Application\Command\CancelOrder;
use App\Order\Application\Command\LockOrder;
use App\Order\Application\Handler\AllocateOrderHandler;
use App\Order\Application\Handler\CancelOrderHandler;
use App\Order\Application\Handler\CreateOrderHandler;
use App\Order\Application\Handler\LockOrderHandler;
use App\Order\Application\Handler\OrderFilterHandler;
use App\Order\Application\Search\OrderSearchCriteria;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\UI\Http\Form\Mapper\CreateOrderMapper;
use App\Order\UI\Http\Form\Mapper\OrderFilterMapper;
use App\Order\UI\Http\Form\Model\OrderForm;
use App\Order\UI\Http\Form\Type\OrderFilterType;
use App\Order\UI\Http\Form\Type\OrderType;
use App\Shared\UI\Http\FormFlow\CommandFlow;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class OrderController extends AbstractController
{
    private function model(): FlowModel
    {
        return FlowModel::simple('order');
    }

    #[Route(path: '/order/', name: 'app_order_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        OrderRepository $repository,
        #[MapQueryString] OrderSearchCriteria $criteria = new OrderSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            repository: $repository,
            criteria: $criteria,
            context: FlowContext::forSearch($this->model()),
        );
    }

    #[Route(path: '/order/search/filter', name: 'app_order_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        OrderFilterMapper $mapper,
        OrderFilterHandler $handler,
        FormFlow $flow,
        #[MapQueryString] OrderSearchCriteria $criteria = new OrderSearchCriteria(),
    ): Response {
        return $flow->form(
            request: $request,
            formType: OrderFilterType::class,
            data: $criteria,
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forFilter($this->model()),
        );
    }

    #[Route(path: '/order/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CreateOrderMapper $mapper,
        CreateOrderHandler $handler,
        FormFlow $flow,
    ): Response {
        return $flow->form(
            request: $request,
            formType: OrderType::class,
            data: new OrderForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate($this->model()),
        );
    }

    #[Route(path: '/order/{id}/cancel/confirm', name: 'app_order_cancel_confirm', methods: ['GET'])]
    public function cancelConfirm(
        #[ValueResolver('public_id')] CustomerOrder $order,
    ): Response {
        return $this->render('/order/cancel.html.twig', ['result' => $order]);
    }

    #[Route(path: '/order/{id}/cancel', name: 'app_order_cancel', methods: ['POST'])]
    public function delete(
        Request $request,
        #[ValueResolver('public_id')] CustomerOrder $order,
        CancelOrderHandler $handler,
        DeleteFlow $flow,
    ): Response {
        return $flow->delete(
            request: $request,
            command: new CancelOrder($order->getPublicId()),
            handler: $handler,
            context: FlowContext::forDelete($this->model()),
        );
    }

    #[Route(path: '/order/{id}/allocate', name: 'app_order_allocate', methods: ['GET'])]
    public function process(
        Request $request,
        #[ValueResolver('public_id')] CustomerOrder $order,
        AllocateOrderHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new AllocateOrder($order->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_order_show', [
                'id' => $order->getPublicId()->value(),
            ]),
        );
    }

    #[Route(path: '/order/{id}/lock/toggle', name: 'app_order_lock_toggle_status', methods: ['GET'])]
    public function cancel(
        Request $request,
        #[ValueResolver('public_id')] CustomerOrder $order,
        LockOrderHandler $handler,
        CommandFlow $flow,
    ): Response {
        return $flow->process(
            request: $request,
            command: new LockOrder($order->getPublicId()),
            handler: $handler,
            context: FlowContext::forSuccess('app_order_show', [
                'id' => $order->getPublicId()->value(),
            ]),
        );
    }

    #[Route(path: '/order/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] CustomerOrder $order): Response
    {
        return $this->render('/order/show.html.twig', ['result' => $order]);
    }
}
