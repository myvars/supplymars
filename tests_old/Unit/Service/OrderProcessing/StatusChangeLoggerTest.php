<?php

namespace App\Tests\Unit\Service\OrderProcessing;

use App\Audit\Infrastructure\Logging\StatusChangeLogWriter;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\Event\StatusWasChangedEventInterface;
use App\Shared\Domain\ValueObject\StatusChange;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StatusChangeLoggerTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private StatusChangeLogWriter $statusChangeLogger;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->statusChangeLogger = new StatusChangeLogWriter($this->em, $this->validator);
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

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->statusChangeLogger->write($event, $user, $legacyId);
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

        $this->statusChangeLogger->write($event, $user, $legacyId);
    }
}
