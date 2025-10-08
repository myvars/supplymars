<?php

namespace App\Tests\Unit\Service\OrderProcessing;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\User;
use App\Enum\DomainEventType;
use App\Enum\OrderStatus;
use App\Event\StatusWasChangedEventInterface;
use App\Service\OrderProcessing\StatusChangedLogger;
use App\ValueObject\StatusChange;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StatusChangeLoggerTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $validator;

    private StatusChangedLogger $statusChangeLogger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->statusChangeLogger = new StatusChangedLogger($this->entityManager, $this->validator);
    }

    public function testFromStatusChangeEventSuccessfully(): void
    {
        $legacyId = 123;
        $statusChange = StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING);
        $user = $this->createMock(User::class);
        $event = $this->createMock(StatusWasChangedEventInterface::class);
        $event->method('type')->willReturn(DomainEventType::ORDER_STATUS_CHANGED);
        $event->method('statusChange')->willReturn($statusChange);
        $event->method('occurredAt')->willReturn(new \DateTimeImmutable());

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->statusChangeLogger->fromStatusWasChangedEvent($event, $user, $legacyId);
    }

    public function testFromStatusChangeEventThrowsExceptionOnValidationFailure(): void
    {
        $legacyId = 123;
        $statusChange = StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING);
        $user = $this->createMock(User::class);
        $event = $this->createMock(StatusWasChangedEventInterface::class);
        $event->method('type')->willReturn(DomainEventType::ORDER_STATUS_CHANGED);
        $event->method('statusChange')->willReturn($statusChange);
        $event->method('occurredAt')->willReturn(new \DateTimeImmutable());

        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(1);
        $violationList->method('__toString')->willReturn('Validation error');

        $this->validator->method('validate')->willReturn($violationList);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        $this->statusChangeLogger->fromStatusWasChangedEvent($event, $user, $legacyId);
    }
}
