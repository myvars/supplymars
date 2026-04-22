<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/** Marker: events implementing this are also dispatched to Messenger (async). */
interface AsyncDomainEventInterface extends DomainEventInterface
{
}
