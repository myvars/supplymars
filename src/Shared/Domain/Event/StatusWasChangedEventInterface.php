<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Shared\Domain\ValueObject\AbstractUlidId;
use App\Shared\Domain\ValueObject\StatusChange;

interface StatusWasChangedEventInterface extends DomainEventInterface
{
    public function getId(): AbstractUlidId;

    public function getStatusChange(): StatusChange;
}
