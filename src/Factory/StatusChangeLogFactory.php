<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use App\Entity\StatusChangeLog;
use App\Enum\DomainEventType;
use Zenstruck\Foundry\LazyValue;

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
            'user' => LazyValue::memoize(fn (): UserFactory => UserFactory::new()),
            'eventTimestamp' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(StatusChangeLog $statusChangeLog): void {})
        ;
    }
}
