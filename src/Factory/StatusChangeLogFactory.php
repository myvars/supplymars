<?php

namespace App\Factory;

use App\Entity\StatusChangeLog;
use App\Enum\DomainEventType;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<StatusChangeLog>
 */
final class StatusChangeLogFactory extends PersistentProxyObjectFactory
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
    protected function defaults(): array|callable
    {
        return [
            'eventType' => self::faker()->randomElement(DomainEventType::cases()),
            'eventTypeId' => self::faker()->numberBetween(1, 100),
            'status' => self::faker()->text(255),
            'user' => UserFactory::new(),
            'eventTimestamp' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(StatusChangeLog $statusChangeLog): void {})
        ;
    }
}
