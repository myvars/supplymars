<?php

namespace App\Event;

/** Marker: events implementing this are also dispatched to Messenger (async). */
interface AsyncDomainEventInterface extends DomainEventInterface
{
}
