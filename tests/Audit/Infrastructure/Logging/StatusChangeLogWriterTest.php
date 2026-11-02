<?php

declare(strict_types=1);

namespace App\Tests\Audit\Infrastructure\Logging;

use App\Audit\Infrastructure\Logging\StatusChangeLogWriter;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\StatusChange;
use App\Tests\Shared\Factory\StatusChangeLogFactory;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StatusChangeLogWriterTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private StatusChangeLogWriter $writer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->writer = self::getContainer()->get(StatusChangeLogWriter::class);
    }

    public function testWritesValidLog(): void
    {
        $this->writer->write(
            type: DomainEventType::ORDER_STATUS_CHANGED,
            entityId: 123,
            statusChange: StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING),
            occurredAt: new \DateTimeImmutable(),
            currentUser: UserFactory::createOne(),
        );
        $this->em->clear();

        $rows = StatusChangeLogFactory::repository()->findBy(['eventTypeId' => 123]);
        self::assertCount(1, $rows);
        self::assertSame('PROCESSING', $rows[0]->getStatus());
        self::assertSame(DomainEventType::ORDER_STATUS_CHANGED, $rows[0]->getEventType());
    }

    public function testWritesWithDifferentEventTypes(): void
    {
        $this->writer->write(
            type: DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            entityId: 456,
            statusChange: StatusChange::from(OrderStatus::PENDING, OrderStatus::SHIPPED),
            occurredAt: new \DateTimeImmutable(),
            currentUser: UserFactory::createOne(),
        );
        $this->em->clear();

        $rows = StatusChangeLogFactory::repository()->findBy(['eventTypeId' => 456]);
        self::assertCount(1, $rows);
        self::assertSame(DomainEventType::PURCHASE_ORDER_STATUS_CHANGED, $rows[0]->getEventType());
        self::assertSame('SHIPPED', $rows[0]->getStatus());
    }

    public function testUserIsCorrectlyAssociated(): void
    {
        $user = UserFactory::createOne();

        $this->writer->write(
            type: DomainEventType::ORDER_ITEM_STATUS_CHANGED,
            entityId: 789,
            statusChange: StatusChange::from(OrderStatus::PENDING, OrderStatus::DELIVERED),
            occurredAt: new \DateTimeImmutable(),
            currentUser: $user,
        );
        $this->em->clear();

        $rows = StatusChangeLogFactory::repository()->findBy(['eventTypeId' => 789]);
        self::assertCount(1, $rows);
        self::assertSame($user->getId(), $rows[0]->getUser()->getId());
    }

    #[TestWith([0], 'zero')]
    #[TestWith([-5], 'negative')]
    public function testRefusesEntityIdThatIsNotPositive(int $entityId): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->writer->write(
            type: DomainEventType::ORDER_STATUS_CHANGED,
            entityId: $entityId,
            statusChange: StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING),
            occurredAt: new \DateTimeImmutable(),
            currentUser: UserFactory::createOne(),
        );
    }
}
