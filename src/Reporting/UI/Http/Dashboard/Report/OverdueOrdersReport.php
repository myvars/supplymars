<?php

namespace App\Reporting\UI\Http\Dashboard\Report;

use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Reporting\Application\Search\OverdueOrderSearchCriteria;
use App\Shared\Infrastructure\Persistence\Search\Paginator;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('overdue-orders')]
final class OverdueOrdersReport implements ReportInterface
{
    private OverdueOrderSearchCriteria $dto;

    public function __construct(
        private readonly CustomerOrderDoctrineRepository $orderRepository,
        private readonly Paginator $paginator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(object $dto): array
    {
        if (!$dto instanceof OverdueOrderSearchCriteria) {
            throw new \InvalidArgumentException('Invalid DTO');
        }

        $this->dto = $dto;

        try {
            $overdueOrders = $this->getOverdueOrders();
        } catch (OutOfRangeCurrentPageException) {
            $this->dto->setPage(1);
            $overdueOrders = $this->getOverdueOrders();
        }

        return [
            'summary' => $this->getSummary(),
            'overdue' => $overdueOrders,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSummary(): array
    {
        $summary = $this->orderRepository->findOverdueOrdersSummary(
            new \DateTime($this->dto->getDuration()->getStartDate())
        );

        return $summary ?? [];
    }

    /**
     * @return Pagerfanta<mixed>
     */
    private function getOverdueOrders(): Pagerfanta
    {
        return $this->paginator->createPagination(
            $this->orderRepository->findOverdueOrders($this->dto),
            $this->dto->getPage(),
            $this->dto->getLimit()
        );
    }
}
