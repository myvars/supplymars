<?php

namespace App\Tests\Integration\Service\OrderProcessing;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Infrastructure\Logging\StatusChangeLogWriter;
use App\Order\Domain\Model\Order\Event\OrderStatusWasChangedEvent;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Shared\Domain\ValueObject\StatusChange;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;

class StatusChangeLoggerIntegrationTest extends KernelTestCase
{
    use Factories;

    private StatusChangeLogWriter $statusChangeLogger;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->statusChangeLogger = new StatusChangeLogWriter($em, $validator);
    }

    public function testFromStatusChangeEventSuccessfully(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $customerOrder = CustomerOrderFactory::new()->create();

        $event = new OrderStatusWasChangedEvent(
            $customerOrder->getPublicId(),
            StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING)
        );

        $this->statusChangeLogger->write(
            $event,
            $user,
            $customerOrder->getId()
        );

        $statusChangeLog = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(StatusChangeLog::class)
            ->findOneBy([
                'eventTypeId' => $customerOrder->getId(),
                'eventType' => $event->type(),
                'status' => OrderStatus::PROCESSING->value,
            ]);

        $this->assertInstanceOf(StatusChangeLog::class, $statusChangeLog);
        $this->assertSame($user, $statusChangeLog->getUser());
        $this->assertSame($event->type(), $statusChangeLog->getEventType());
        $this->assertSame(OrderStatus::PROCESSING->value, $statusChangeLog->getStatus());
    }
}
