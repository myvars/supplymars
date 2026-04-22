<?php

declare(strict_types=1);

namespace App\Note\Application\Command\Ticket;

final readonly class CreateTicket
{
    public function __construct(
        public int $poolId,
        public int $customerId,
        public string $subject,
        public string $body,
    ) {
    }
}
