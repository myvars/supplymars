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
use App\Service\Crud\CrudHelper;
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
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    public const SECTION = 'Order';

    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $crudSearcher,
        CustomerOrderRepository $repository,
        #[MapQueryString] OrderSearchDto $dto = new OrderSearchDto()
    ): Response {
        return $crudSearcher->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route('/search/filter', name: 'app_order_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        CrudHandler $crudHandler,
        SearchFilter $action,
        #[MapQueryString] OrderSearchDto $dto = new OrderSearchDto()
    ): Response {
        $dto->setQueryString($request->getQueryString());
        $form = $this->createForm(OrderSearchFilterType::class, $dto, [
            'action' => $this->generateUrl('app_order_search_filter', $request->query->all()),
        ]);

        return $crudHandler->build($crudHandler->getOptions()
            ->setTemplate('common/search_filter.html.twig')
            ->setForm($form)
            ->setEntity($dto)
            ->setCrudAction($action)
            ->setSuccessLink($this->generateUrl('app_order_index'))
        );
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
            ->setSuccessLink($this->generateUrl('app_order_index'))
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

    #[Route('/{id}/cancel/confirm', name: 'app_order_cancel_confirm', methods: ['GET'])]
    public function cancelConfirm(?CustomerOrder $customerOrder, CrudHelper $crudHelper): Response
    {
        if (!$customerOrder instanceof CustomerOrder) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        return $this->render('order/cancel.html.twig', [
            'section' => self::SECTION,
            'result' => $customerOrder,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_order_cancel', methods: ['POST'])]
    public function cancel(?CustomerOrder $customerOrder, CancelOrder $action, CrudHelper $crudHelper): Response
    {
        if (!$customerOrder instanceof CustomerOrder) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        if ($this->isCsrfTokenValid(
            'delete'.$customerOrder->getId(), $crudHelper->getRequest()->get('_token'))
        ) {
            try {
                $action->cancel($customerOrder);
                $this->addFlash('success', 'Order cancelled successfully');
            } catch (\Exception) {
                $this->addFlash('error', 'Order cannot be cancelled');
            }
        }

        return $crudHelper->redirectToLink(
            $this->generateUrl('app_order_show', ['id' => $customerOrder->getId()])
        );
    }

    #[Route('/{id}/process', name: 'app_order_process', methods: ['GET'])]
    public function process(?CustomerOrder $customerOrder, ProcessOrder $action, CrudHelper $crudHelper): Response
    {
        if (!$customerOrder instanceof CustomerOrder) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        try {
            $action->processOrder($customerOrder);
            $this->addFlash('success', 'Order processed successfully');
        } catch (\Exception) {
            $this->addFlash('error', 'Order cannot be processed');
        }

        return $crudHelper->redirectToLink(
            $this->generateUrl('app_order_show', ['id' => $customerOrder->getId()])
        );
    }

    #[Route('/{id}/lock/toggle', name: 'app_order_lock_toggle_status', methods: ['GET'])]
    public function toggleStatus(?CustomerOrder $customerOrder, LockOrder $action, CrudHelper $crudHelper): Response
    {
        if (!$customerOrder instanceof CustomerOrder) {
            return $crudHelper->showEmpty(self::SECTION);
        }

        $action->toggleStatus($customerOrder);

        return $crudHelper->redirectToLink(
            $this->generateUrl('app_order_show', ['id' => $customerOrder->getId()])
        );
    }
}
