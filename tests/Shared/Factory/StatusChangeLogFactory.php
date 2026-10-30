<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Customer\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventType;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<StatusChangeLog>
 */
final class StatusChangeLogFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return StatusChangeLog::class;
    }

    protected function defaults(): array
    {
        return [
            'eventType' => self::faker()->randomElement(DomainEventType::cases()),
            'eventTypeId' => self::faker()->numberBetween(1, 100),
            'status' => self::faker()->text(30),
            'user' => LazyValue::memoize(fn (): User => UserFactory::createOne()),
            'eventTimestamp' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }
}
