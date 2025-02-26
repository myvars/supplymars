<?php

namespace App\Tests\Integration\Entity;

use App\Enum\DomainEventType;
use App\Factory\StatusChangeLogFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class StatusChangeLogIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidStatusChangeLog(): void
    {
        $user = UserFactory::createOne();

        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventTypeId' => 1,
            'status' => 'SHIPPED',
        ]);

        $errors = $this->validator->validate($statusChangeLog);
        $this->assertCount(0, $errors);
    }

    public function testEventTypeIdIsPositive(): void
    {
        $statusChangeLog = StatusChangeLogFactory::createOne(['eventTypeId' => -1]);

        $errors = $this->validator->validate($statusChangeLog);
        $this->assertSame('Please enter a positive event type Id', $errors[0]->getMessage());
    }

    public function testStatusIsRequired(): void
    {
        $statusChangeLog = StatusChangeLogFactory::createOne(['status' => '']);

        $errors = $this->validator->validate($statusChangeLog);
        $this->assertSame('Please enter a status', $errors[0]->getMessage());
    }

    public function testStatusChangeLogPersistence(): void
    {
        $user = UserFactory::createOne();

        $statusChangeLog = StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::ORDER_STATUS_CHANGED,
            'eventTypeId' => 1,
            'status' => 'SHIPPED',
            'user' => $user,
            'eventTimestamp' => new \DateTimeImmutable()
        ])->_disableAutoRefresh();

        $persistedStatusChangeLog = StatusChangeLogFactory::repository()->find($statusChangeLog->getId());
        $this->assertEquals('SHIPPED', $persistedStatusChangeLog->getStatus());
    }
}