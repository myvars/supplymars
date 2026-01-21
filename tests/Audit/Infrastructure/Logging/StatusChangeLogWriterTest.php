<?php

namespace App\Tests\Audit\Infrastructure\Logging;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Audit\Infrastructure\Logging\StatusChangeLogWriter;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\StatusChange;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

final class StatusChangeLogWriterTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    private StatusChangeLogRepository $repo;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repo = self::getContainer()->get(StatusChangeLogRepository::class);
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    private function createUser(): User
    {
        // UserFactory returns a Proxy which extends User, so we can return it directly
        return UserFactory::createOne();
    }

    public function testWritesValidLogAndFlushes(): void
    {
        $user = $this->createUser();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $writer = new StatusChangeLogWriter($this->repo, $this->validator, $flusher);

        $writer->write(
            type: DomainEventType::ORDER_STATUS_CHANGED,
            entityId: 123,
            statusChange: StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING),
            occurredAt: new \DateTimeImmutable(),
            currentUser: $user,
        );

        $this->em->flush();
        $this->em->clear();

        $rows = $this->em->getRepository(StatusChangeLog::class)->findBy(['eventTypeId' => 123]);
        self::assertCount(1, $rows);
        self::assertSame('PROCESSING', $rows[0]->getStatus());
        self::assertSame(DomainEventType::ORDER_STATUS_CHANGED, $rows[0]->getEventType());
    }

    public function testThrowsOnNonPositiveEventTypeId(): void
    {
        $user = $this->createUser();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $writer = new StatusChangeLogWriter($this->repo, $this->validator, $flusher);

        $this->expectException(\InvalidArgumentException::class);

        $writer->write(
            type: DomainEventType::ORDER_STATUS_CHANGED,
            entityId: 0, // invalid per Positive constraint
            statusChange: StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING),
            occurredAt: new \DateTimeImmutable(),
            currentUser: $user,
        );
    }

    public function testThrowsOnNegativeEventTypeId(): void
    {
        $user = $this->createUser();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $writer = new StatusChangeLogWriter($this->repo, $this->validator, $flusher);

        $this->expectException(\InvalidArgumentException::class);

        $writer->write(
            type: DomainEventType::ORDER_STATUS_CHANGED,
            entityId: -5, // invalid per Positive constraint
            statusChange: StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING),
            occurredAt: new \DateTimeImmutable(),
            currentUser: $user,
        );
    }

    public function testWritesWithDifferentEventTypes(): void
    {
        $user = $this->createUser();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $writer = new StatusChangeLogWriter($this->repo, $this->validator, $flusher);

        $writer->write(
            type: DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            entityId: 456,
            statusChange: StatusChange::from(OrderStatus::PENDING, OrderStatus::SHIPPED),
            occurredAt: new \DateTimeImmutable(),
            currentUser: $user,
        );

        $this->em->flush();
        $this->em->clear();

        $rows = $this->em->getRepository(StatusChangeLog::class)->findBy(['eventTypeId' => 456]);
        self::assertCount(1, $rows);
        self::assertSame(DomainEventType::PURCHASE_ORDER_STATUS_CHANGED, $rows[0]->getEventType());
        self::assertSame('SHIPPED', $rows[0]->getStatus());
    }

    public function testUserIsCorrectlyAssociated(): void
    {
        $user = $this->createUser();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $writer = new StatusChangeLogWriter($this->repo, $this->validator, $flusher);

        $writer->write(
            type: DomainEventType::ORDER_ITEM_STATUS_CHANGED,
            entityId: 789,
            statusChange: StatusChange::from(OrderStatus::PENDING, OrderStatus::DELIVERED),
            occurredAt: new \DateTimeImmutable(),
            currentUser: $user,
        );

        $this->em->flush();
        $this->em->clear();

        $rows = $this->em->getRepository(StatusChangeLog::class)->findBy(['eventTypeId' => 789]);
        self::assertCount(1, $rows);
        self::assertSame($user->getId(), $rows[0]->getUser()->getId());
    }
}
