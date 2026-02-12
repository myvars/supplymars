<?php

namespace App\Tests\Audit\Application\Listener;

use App\Audit\Application\EventListener\StatusChangeLogger;
use App\Audit\Infrastructure\Logging\StatusChangeLogWriter;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\Event\OrderItemStatusWasChangedEvent;
use App\Order\Domain\Model\Order\Event\OrderStatusWasChangedEvent;
use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderItemStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderStatusWasChangedEvent;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItemPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Shared\Application\Identity\PublicIdResolverRegistry;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\StatusChange;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
final class StatusChangeLoggerTest extends TestCase
{
    /** @phpstan-ignore property.unresolvableNativeType */
    private StatusChangeLogWriter&MockObject $writer;

    /** @phpstan-ignore property.unresolvableNativeType */
    private PublicIdResolverRegistry&Stub $resolver;

    private StatusChangeLogger $listener;

    private User $user;

    protected function setUp(): void
    {
        // @phpstan-ignore method.unresolvableReturnType
        $this->writer = $this->createMock(StatusChangeLogWriter::class);
        // @phpstan-ignore method.unresolvableReturnType
        $currentUserProvider = $this->createStub(CurrentUserProvider::class);
        // @phpstan-ignore method.unresolvableReturnType
        $this->resolver = $this->createStub(PublicIdResolverRegistry::class);
        $logger = $this->createStub(LoggerInterface::class);

        $this->listener = new StatusChangeLogger(
            changeLogWriter: $this->writer,
            currentUserProvider: $currentUserProvider,
            publicIdResolverRegistry: $this->resolver,
            logger: $logger,
        );

        $this->user = $this->createStub(User::class);
        $currentUserProvider->method('get')->willReturn($this->user);
    }

    public function testItWritesLogForOrderStatusEvent(): void
    {
        $publicId = OrderPublicId::new();
        $statusChange = StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING);
        $event = new OrderStatusWasChangedEvent(
            id: $publicId,
            statusChange: $statusChange,
        );

        $this->resolver->method('resolve')->with($publicId)->willReturn(123);

        $this->writer
            ->expects(self::once())
            ->method('write')
            ->with(
                self::equalTo(DomainEventType::ORDER_STATUS_CHANGED),
                self::equalTo(123),
                self::equalTo($statusChange),
                self::isInstanceOf(\DateTimeImmutable::class),
                self::equalTo($this->user)
            );

        ($this->listener)($event);
    }

    public function testItWritesLogForOrderItemStatusEvent(): void
    {
        $publicId = OrderItemPublicId::new();
        $statusChange = StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING);
        $event = new OrderItemStatusWasChangedEvent(
            id: $publicId,
            statusChange: $statusChange,
        );

        $this->resolver->method('resolve')->with($publicId)->willReturn(456);

        $this->writer
            ->expects(self::once())
            ->method('write')
            ->with(
                self::equalTo(DomainEventType::ORDER_ITEM_STATUS_CHANGED),
                self::equalTo(456),
                self::equalTo($statusChange),
                self::isInstanceOf(\DateTimeImmutable::class),
                self::equalTo($this->user)
            );

        ($this->listener)($event);
    }

    public function testItWritesLogForPurchaseOrderStatusEvent(): void
    {
        $publicId = PurchaseOrderPublicId::new();
        $statusChange = StatusChange::from(PurchaseOrderStatus::PENDING, PurchaseOrderStatus::PROCESSING);
        $event = new PurchaseOrderStatusWasChangedEvent(
            id: $publicId,
            statusChange: $statusChange,
        );

        $this->resolver->method('resolve')->with($publicId)->willReturn(789);

        $this->writer
            ->expects(self::once())
            ->method('write')
            ->with(
                self::equalTo(DomainEventType::PURCHASE_ORDER_STATUS_CHANGED),
                self::equalTo(789),
                self::equalTo($statusChange),
                self::isInstanceOf(\DateTimeImmutable::class),
                self::equalTo($this->user)
            );

        ($this->listener)($event);
    }

    public function testItWritesLogForPurchaseOrderItemStatusEvent(): void
    {
        $publicId = PurchaseOrderItemPublicId::new();
        $statusChange = StatusChange::from(PurchaseOrderStatus::PENDING, PurchaseOrderStatus::PROCESSING);
        $event = new PurchaseOrderItemStatusWasChangedEvent(
            id: $publicId,
            statusChange: $statusChange,
        );

        $this->resolver->method('resolve')->with($publicId)->willReturn(321);

        $this->writer
            ->expects(self::once())
            ->method('write')
            ->with(
                self::equalTo(DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED),
                self::equalTo(321),
                self::equalTo($statusChange),
                self::isInstanceOf(\DateTimeImmutable::class),
                self::equalTo($this->user)
            );

        ($this->listener)($event);
    }

    public function testItSkipsWhenLegacyIdNotResolvedAndWarns(): void
    {
        $publicId = OrderPublicId::new();
        $statusChange = StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING);
        $event = new OrderStatusWasChangedEvent(
            id: $publicId,
            statusChange: $statusChange,
        );

        $this->resolver->method('resolve')->with($publicId)->willReturn(null);

        $this->writer->expects(self::never())->method('write');

        // here we care about logger behavior, so use a mock just for this test
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                self::stringContains('Could not resolve legacy ID for public ID'),
                self::arrayHasKey('publicId')
            );

        // @phpstan-ignore method.unresolvableReturnType
        $userProvider = $this->createStub(CurrentUserProvider::class);
        $this->listener = new StatusChangeLogger(
            changeLogWriter: $this->writer,
            currentUserProvider: $userProvider,
            publicIdResolverRegistry: $this->resolver,
            logger: $logger,
        );

        ($this->listener)($event);
    }

    public function testItPropagatesValidationErrorFromWriter(): void
    {
        $publicId = OrderPublicId::new();
        $statusChange = StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING);
        $event = new OrderStatusWasChangedEvent(
            id: $publicId,
            statusChange: $statusChange,
        );

        $this->resolver->method('resolve')->with($publicId)->willReturn(123);

        $this->writer
            ->expects(self::once())
            ->method('write')
            ->willThrowException(new \InvalidArgumentException('validation errors'));

        $this->expectException(\InvalidArgumentException::class);

        ($this->listener)($event);
    }
}
