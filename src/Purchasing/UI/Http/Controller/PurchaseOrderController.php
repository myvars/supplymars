<?php

namespace App\Purchasing\UI\Http\Controller;

use App\Purchasing\Application\Handler\PurchaseOrder\PurchaseOrderFilterHandler;
use App\Purchasing\Application\Search\PurchaseOrderSearchCriteria;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Repository\PurchaseOrderRepository;
use App\Purchasing\UI\Http\Form\Mapper\PurchaseOrderFilterMapper;
use App\Purchasing\UI\Http\Form\Type\PurchaseOrderFilterType;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PurchaseOrderController extends AbstractController
{
    public const string MODEL = 'purchasing/purchase order';

    #[Route(path: '/purchase/order/', name: 'app_purchasing_purchase_order_index', methods: ['GET'])]
    public function index(
        Request $request,
        SearchFlow $flow,
        PurchaseOrderRepository $repository,
        #[MapQueryString] PurchaseOrderSearchCriteria $criteria = new PurchaseOrderSearchCriteria(),
    ): Response {
        return $flow->search(
            request: $request,
            model: self::MODEL,
            repository: $repository,
            criteria: $criteria,
        );
    }

    #[Route(path: '/purchase/order/search/filter', name: 'app_purchasing_purchase_order_search_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        PurchaseOrderFilterMapper $mapper,
        PurchaseOrderFilterHandler $handler,
        FormFlow $flow,
        #[MapQueryString] PurchaseOrderSearchCriteria $criteria = new PurchaseOrderSearchCriteria(),
    ): Response {
        return $flow->form(
            request: $request,
            formType: PurchaseOrderFilterType::class,
            data: $criteria,
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forFilter(self::MODEL),
        );
    }

    #[Route(path: '/purchase/order/{id}', name: 'app_purchasing_purchase_order_show', methods: ['GET'])]
    public function show(#[ValueResolver('public_id')] PurchaseOrder $purchaseOrder): Response {
        return $this->render('/purchasing/purchase_order/show.html.twig', ['result' => $purchaseOrder]);
    }
}
