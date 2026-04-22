<?php

declare(strict_types=1);

namespace App\Note\Application\Command\Ticket;

use App\Shared\Application\Search\FilterCommand;

final readonly class TicketFilter extends FilterCommand
{
    public function __construct(
        ?string $query,
        string $sort,
        string $sortDirection,
        int $page,
        int $limit,
        public ?int $poolId,
        public ?string $status,
        public bool $showSnoozed,
    ) {
        parent::__construct($query, $sort, $sortDirection, $page, $limit);
    }
}
