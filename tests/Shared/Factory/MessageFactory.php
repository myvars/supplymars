<?php

namespace App\Tests\Shared\Factory;

use App\Customer\Domain\Model\User\User;
use App\Note\Domain\Model\Message\AuthorType;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Message\MessageVisibility;
use App\Note\Domain\Model\Ticket\Ticket;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Message>
 */
final class MessageFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Message::class;
    }

    protected function defaults(): array
    {
        return [
            'ticket' => LazyValue::memoize(fn (): Ticket => TicketFactory::createOne()),
            'author' => LazyValue::memoize(fn (): User => UserFactory::createOne()),
            'authorType' => AuthorType::CUSTOMER,
            'body' => self::faker()->paragraph(),
            'visibility' => MessageVisibility::PUBLIC,
        ];
    }

    public function staff(): self
    {
        return $this->with([
            'author' => LazyValue::memoize(fn (): User => UserFactory::new()->asStaff()->create()),
            'authorType' => AuthorType::STAFF,
        ]);
    }

    public function internal(): self
    {
        return $this->with(['visibility' => MessageVisibility::INTERNAL]);
    }

    public function system(): self
    {
        return $this->with([
            'author' => null,
            'authorType' => AuthorType::SYSTEM,
        ]);
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->instantiateWith(
            Instantiator::namedConstructor('create')
        );
    }
}
