<?php

declare(strict_types=1);

namespace App\Note\Application\Handler\Ticket;

use App\Note\Application\Command\Ticket\TicketFilter;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Application\Search\FilterParamBuilder;

final readonly class TicketFilterHandler
{
    private const string ROUTE = 'app_note_ticket_index';

    public function __construct(private FilterParamBuilder $params)
    {
    }

    public function __invoke(TicketFilter $command): Result
    {
        $extras = [
            'poolId' => $command->poolId,
            'status' => $command->status,
            'showSnoozed' => $command->showSnoozed ?: null,
        ];

        return Result::ok(
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: $this->params->build($command, $extras),
            )
        );
    }
}
