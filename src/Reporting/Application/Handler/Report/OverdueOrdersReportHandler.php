<?php

declare(strict_types=1);

namespace App\Reporting\Application\Handler\Report;

use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Reporting\Application\Report\OverdueOrderReportCriteria;
use App\Shared\Application\Result;
use App\Shared\Infrastructure\Persistence\Search\Paginator;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;

final readonly class OverdueOrdersReportHandler
{
    public function __construct(
        private CustomerOrderDoctrineRepository $orderRepository,
        private Paginator $paginator,
    ) {
    }

    public function __invoke(OverdueOrderReportCriteria $criteria): Result
    {
        try {
            $overdueOrders = $this->getOverdueOrders($criteria);
        } catch (OutOfRangeCurrentPageException) {
            $criteria->setPage(1);
            $overdueOrders = $this->getOverdueOrders($criteria);
        }

        $summary = $this->orderRepository->findOverdueOrdersSummary(
            new \DateTime($criteria->getDuration()->getStartDate())
        );

        return Result::ok('Report created', [
            'summary' => $summary ?? [],
            'overdue' => $overdueOrders,
        ]);
    }

    /**
     * @return Pagerfanta<mixed>
     */
    private function getOverdueOrders(OverdueOrderReportCriteria $criteria): Pagerfanta
    {
        return $this->paginator->createPagination(
            $this->orderRepository->findOverdueOrders($criteria),
            $criteria->getPage(),
            $criteria->getLimit()
        );
    }
}
