<?php

declare(strict_types=1);

namespace App\Note\UI\Http\Form\Mapper;

use App\Note\Application\Command\Ticket\TicketFilter;
use App\Note\Application\Search\TicketSearchCriteria;

final class TicketFilterMapper
{
    public function __invoke(TicketSearchCriteria $criteria): TicketFilter
    {
        return new TicketFilter(
            $criteria->getQuery(),
            $criteria->getSort(),
            $criteria->getSortDirection(),
            $criteria->getPage(),
            $criteria->getLimit(),
            $criteria->poolId,
            $criteria->status,
            $criteria->showSnoozed,
        );
    }
}
