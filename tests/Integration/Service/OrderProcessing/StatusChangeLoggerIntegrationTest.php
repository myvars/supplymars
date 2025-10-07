<?php

namespace App\Tests\Integration\Service\OrderProcessing;

use App\Entity\StatusChangeLog;
use App\Enum\OrderStatus;
use App\Event\OrderStatusWasChangedEvent;
use App\Factory\CustomerOrderFactory;
use App\Factory\UserFactory;
use App\Service\OrderProcessing\StatusChangedLogger;
use App\ValueObject\StatusChange;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class StatusChangeLoggerIntegrationTest extends KernelTestCase
{
    use Factories;

    private StatusChangedLogger $statusChangeLogger;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->statusChangeLogger = new StatusChangedLogger($entityManager, $validator);
    }

    public function testFromStatusChangeEventSuccessfully(): void
    {
        $user = UserFactory::new()->staff()->create()->_real();
        $customerOrder = CustomerOrderFactory::new()->create()->_real();

        $event = new OrderStatusWasChangedEvent(
            $customerOrder->getPublicId(),
            StatusChange::from(OrderStatus::PENDING, OrderStatus::PROCESSING)
        );

        $this->statusChangeLogger->fromStatusWasChangedEvent(
            $event,
            $user,
            $customerOrder->getId()
        );

        $statusChangeLog = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(StatusChangeLog::class)
            ->findOneBy([
                'eventTypeId' => $customerOrder->getId(),
                'eventType' => $event->type(),
                'status' => OrderStatus::PROCESSING->value
            ]);

        $this->assertInstanceOf(StatusChangeLog::class, $statusChangeLog);
        $this->assertSame($user, $statusChangeLog->getUser());
        $this->assertSame($event->type(), $statusChangeLog->getEventType());
        $this->assertSame(OrderStatus::PROCESSING->value, $statusChangeLog->getStatus());
    }
}
