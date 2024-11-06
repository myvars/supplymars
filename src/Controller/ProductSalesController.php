<?php

namespace App\Controller;

use App\DTO\ProductSalesFilterDto;
use App\Form\ProductSalesFilterType;
use App\Repository\PurchaseOrderItemRepository;
use App\Service\Crud\CrudHandler;
use App\Service\Sales\SalesFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sales')]
#[IsGranted('ROLE_ADMIN')]
class ProductSalesController extends AbstractController
{
    public const SECTION = 'Product Sales';

    #[Route('/best', name: 'app_product_sales_list', methods: ['GET'])]
    public function best(
        PurchaseOrderItemRepository $repository,
        #[MapQueryString] ProductSalesFilterDto $dto = new ProductSalesFilterDto()
    ): Response {

        return $this->render('sales/sales_list.html.twig', [
            'results' => $repository->findBySalesDto($dto)
        ]);
    }

    #[Route('/filter', name: 'app_product_sales_filter', methods: ['GET', 'POST'])]
    public function searchFilter(
        Request $request,
        CrudHandler $crudHandler,
        SalesFilter $action,
        #[MapQueryString] ProductSalesFilterDto $dto = new ProductSalesFilterDto()
    ): Response {
        $dto->setQueryString($request->getQueryString());
        $form = $this->createForm(ProductSalesFilterType::class, $dto, [
            'action' => $this->generateUrl('app_product_sales_filter', $request->query->all()),
        ]);

        return $crudHandler->build($crudHandler->getOptions()
            ->setTemplate($dto::TEMPLATE)
            ->setForm($form)
            ->setEntity($dto)
            ->setCrudAction($action)
            ->setSuccessLink($this->generateUrl('app_product_sales_list'))
        );
    }
}
