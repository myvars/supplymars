<?php

namespace App\Tests\Shared\Factory;

use App\Customer\Domain\Model\User\User;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Model\Ticket\Ticket;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Ticket>
 */
final class TicketFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Ticket::class;
    }

    protected function defaults(): array
    {
        return [
            'pool' => LazyValue::memoize(fn (): Pool => PoolFactory::createOne()),
            'customer' => LazyValue::memoize(fn (): User => UserFactory::createOne()),
            'subject' => ucfirst(implode(' ', (array) self::faker()->words(random_int(3, 6)))),
            'body' => self::faker()->paragraph(),
        ];
    }

    public function closed(): self
    {
        return $this->afterInstantiate(function (Ticket $ticket): void {
            $ticket->close();
        });
    }

    public function snoozed(\DateTimeImmutable $until = new \DateTimeImmutable('+1 day')): self
    {
        return $this->afterInstantiate(function (Ticket $ticket) use ($until): void {
            $ticket->snooze($until);
        });
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }
}
