<?php

namespace App\Service\Dashboard\Report;

use App\DTO\SearchDto\OverdueOrderSearchDto;
use App\Repository\CustomerOrderRepository;
use App\Service\Search\Paginator;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('overdue-orders')]
class OverdueOrdersReport implements ReportInterface
{
    private readonly OverdueOrderSearchDto $dto;

    public function __construct(
        private readonly CustomerOrderRepository $orderRepository,
        private readonly Paginator $paginator,
    ) {
    }

    public function build(object $dto): ?array
    {
        if (!$dto instanceof OverdueOrderSearchDto) {
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

    private function getSummary(): ?array
    {
        $summary = $this->orderRepository->findOverdueOrdersSummary(
            new \DateTime($this->dto->getDuration()->getStartDate())
        );

        return $summary ?? [];
    }

    private function getOverdueOrders(): Pagerfanta
    {
        return $this->paginator->createPagination(
            $this->orderRepository->findOverdueOrders($this->dto),
            $this->dto->getPage() ?: 1,
            $this->dto->getLimit() ?: $this->dto::LIMIT_DEFAULT
        );
    }
}
