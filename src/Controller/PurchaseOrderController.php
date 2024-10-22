<?php

namespace App\Controller;

use App\DTO\SearchDto\PurchaseOrderSearchDto;
use App\Entity\PurchaseOrder;
use App\Form\ChangePurchaseOrderItemStatusType;
use App\Form\SearchForm\PurchaseOrderSearchFilterType;
use App\Repository\PurchaseOrderRepository;
use App\Service\Crud\CrudDeleter;
use App\Service\Crud\CrudHandler;
use App\Service\Crud\CrudReader;
use App\Service\Crud\CrudSearcher;
use App\Service\Crud\CrudUpdater;
use App\Service\Search\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/purchase/order')]
#[IsGranted('ROLE_ADMIN')]
class PurchaseOrderController extends AbstractController
{
    public const SECTION = 'Purchase Order';

    #[Route('/', name: 'app_purchase_order_index', methods: ['GET'])]
    public function index(
        Request $request,
        CrudSearcher $crudSearcher,
        PurchaseOrderRepository $repository,
        #[MapQueryString] PurchaseOrderSearchDto $dto = new PurchaseOrderSearchDto()
    ): Response {
        return $crudSearcher->search(self::SECTION, $dto, $repository, $request->query->all());
    }

    #[Route('/search/filter', name: 'app_purchase_order_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        CrudHandler $crudHandler,
        SearchFilter $action,
        #[MapQueryString] PurchaseOrderSearchDto $dto = new PurchaseOrderSearchDto()
    ): Response {
        $dto->setQueryString($request->getQueryString());
        $form = $this->createForm(PurchaseOrderSearchFilterType::class, $dto, [
            'action' => $this->generateUrl('app_purchase_order_search_filter', $request->query->all()),
        ]);

        return $crudHandler->build($crudHandler->getOptions()
            ->setTemplate($dto::TEMPLATE)
            ->setForm($form)
            ->setEntity($dto)
            ->setCrudAction($action)
            ->setSuccessLink($this->generateUrl('app_purchase_order_index'))
        );
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
