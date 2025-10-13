<?php

namespace App\Shared\Domain\Event;

use App\Shared\Domain\ValueObject\AbstractUlidId;
use App\Shared\Domain\ValueObject\StatusChange;

interface StatusWasChangedEventInterface extends DomainEventInterface
{
    public function getId(): AbstractUlidId;

    public function getStatusChange(): StatusChange;
}
