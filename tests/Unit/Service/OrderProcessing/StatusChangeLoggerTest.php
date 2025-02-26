<?php

namespace App\Tests\Unit\Service\OrderProcessing;

use App\Entity\User;
use App\Enum\DomainEventType;
use App\Enum\OrderStatus;
use App\Event\DomainEvent;
use App\Service\OrderProcessing\StatusChangeLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StatusChangeLoggerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private StatusChangeLogger $statusChangeLogger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->statusChangeLogger = new StatusChangeLogger($this->entityManager, $this->validator);
    }

    public function testFromStatusChangeEventSuccessfully(): void
    {
        $user = $this->createMock(User::class);
        $event = $this->createMock(DomainEvent::class);
        $event->method('getDomainEventType')->willReturn(DomainEventType::ORDER_STATUS_CHANGED);
        $event->method('getUser')->willReturn($user);
        $event->method('getEventTimestamp')->willReturn(new \DateTimeImmutable());

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->statusChangeLogger->fromStatusChangeEvent($event, 1, OrderStatus::PROCESSING->value);
    }

    public function testFromStatusChangeEventThrowsExceptionOnValidationFailure(): void
    {
        $user = $this->createMock(User::class);
        $event = $this->createMock(DomainEvent::class);
        $event->method('getDomainEventType')->willReturn(DomainEventType::ORDER_STATUS_CHANGED);
        $event->method('getUser')->willReturn($user);
        $event->method('getEventTimestamp')->willReturn(new \DateTimeImmutable());

        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violationList->method('count')->willReturn(1);
        $violationList->method('__toString')->willReturn('Validation error');

        $this->validator->method('validate')->willReturn($violationList);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        $this->statusChangeLogger->fromStatusChangeEvent($event, 1, OrderStatus::PROCESSING->value);
    }
}