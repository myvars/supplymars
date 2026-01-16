<?php

namespace App\Shared\Domain\Event;

/** Marker: events implementing this are also dispatched to Messenger (async). */
interface AsyncDomainEventInterface extends DomainEventInterface
{
}
