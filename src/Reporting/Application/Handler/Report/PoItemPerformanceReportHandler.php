<?php

namespace App\Reporting\Application\Handler\Report;

use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderItemDoctrineRepository;
use App\Reporting\Application\Report\PoItemPerformanceReportCriteria;
use App\Shared\Application\Result;
use App\Shared\Infrastructure\Persistence\Search\Paginator;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;

final readonly class PoItemPerformanceReportHandler
{
    public function __construct(
        private PurchaseOrderItemDoctrineRepository $purchaseOrderItemRepository,
        private Paginator $paginator,
    ) {
    }

    public function __invoke(PoItemPerformanceReportCriteria $criteria): Result
    {
        try {
            $items = $this->getPerformanceItems($criteria);
        } catch (OutOfRangeCurrentPageException) {
            $criteria->setPage(1);
            $items = $this->getPerformanceItems($criteria);
        }

        $summary = $this->purchaseOrderItemRepository->findPerformanceReportSummary(
            new \DateTime($criteria->getDuration()->getStartDate()),
            new \DateTime($criteria->getDuration()->getEndDate())
        );

        return Result::ok('Report created', [
            'summary' => $summary ?? [],
            'items' => $items,
        ]);
    }

    /**
     * @return Pagerfanta<mixed>
     */
    private function getPerformanceItems(PoItemPerformanceReportCriteria $criteria): Pagerfanta
    {
        return $this->paginator->createPagination(
            $this->purchaseOrderItemRepository->findForPerformanceReport($criteria),
            $criteria->getPage(),
            $criteria->getLimit()
        );
    }
}
