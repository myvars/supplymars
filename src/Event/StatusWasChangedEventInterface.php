<?php

namespace App\Event;

use App\ValueObject\AbstractUlidId;
use App\ValueObject\StatusChange;

interface StatusWasChangedEventInterface extends DomainEventInterface
{
    public function publicId(): AbstractUlidId;

    public function statusChange(): StatusChange;
}
