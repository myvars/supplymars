<?php

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
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return StatusChangeLog::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
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

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
