<?php

declare(strict_types=1);

namespace App\Order\Domain\Model\Order\Event;

use App\Order\Domain\Model\Order\OrderPublicId;
use App\Shared\Domain\Event\AbstractDomainEvent;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\Event\StatusWasChangedEventInterface;
use App\Shared\Domain\ValueObject\StatusChange;

final class OrderStatusWasChangedEvent extends AbstractDomainEvent implements StatusWasChangedEventInterface
{
    public function __construct(
        private readonly OrderPublicId $id,
        private readonly StatusChange $statusChange,
    ) {
        parent::__construct(DomainEventType::ORDER_STATUS_CHANGED);
    }

    public function getId(): OrderPublicId
    {
        return $this->id;
    }

    public function getStatusChange(): StatusChange
    {
        return $this->statusChange;
    }
}
