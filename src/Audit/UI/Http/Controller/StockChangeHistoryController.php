<?php

namespace App\Audit\UI\Http\Controller;

use App\Audit\Domain\Model\StockChange\HistoryDuration;
use App\Audit\Domain\Repository\SupplierStockChangeLogRepository;
use App\Audit\UI\Http\Chart\StockHistoryChartBuilder;
use App\Catalog\Domain\Model\Product\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class StockChangeHistoryController extends AbstractController
{
    public function __construct(
        private readonly SupplierStockChangeLogRepository $logRepository,
        private readonly StockHistoryChartBuilder $chartBuilder,
    ) {
    }

    #[Route(path: '/product/{id}/history', name: 'app_catalog_product_history', methods: ['GET'])]
    public function history(
        #[ValueResolver('public_id')] Product $product,
        Request $request,
    ): Response {
        $durationValue = $request->query->getString('duration');
        $duration = HistoryDuration::tryFrom($durationValue) ?? HistoryDuration::default();

        $supplierProducts = $product->getSupplierProducts();
        $supplierProductIds = [];
        $supplierNames = [];
        $supplierColours = [];

        foreach ($supplierProducts as $supplierProduct) {
            $id = $supplierProduct->getId();
            if ($id === null) {
                continue;
            }

            $supplier = $supplierProduct->getSupplier();
            $supplierProductIds[] = $id;
            $supplierNames[$id] = $supplier->getName() ?? 'Unknown';
            $supplierColours[$id] = $supplier->getColourScheme();
        }

        $logs = $this->logRepository->findBySupplierProductIds(
            $supplierProductIds,
            $duration->getSinceDate(),
        );

        $hasData = $logs !== [];
        $stockChart = $hasData ? $this->chartBuilder->createStockChart($logs, $supplierNames, $supplierColours) : null;
        $costChart = $hasData ? $this->chartBuilder->createCostChart($logs, $supplierNames, $supplierColours) : null;

        return $this->render('audit/product_history.html.twig', [
            'result' => $product,
            'stockChart' => $stockChart,
            'costChart' => $costChart,
            'duration' => $duration,
            'hasData' => $hasData,
        ]);
    }
}
