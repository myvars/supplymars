<?php

declare(strict_types=1);

namespace App\Tests\Note\Domain;

use App\Customer\Domain\Model\User\User;
use App\Note\Domain\Model\Message\AuthorType;
use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Model\Message\MessageVisibility;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Model\Ticket\Ticket;
use App\Note\Domain\Model\Ticket\TicketStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TicketDomainTest extends TestCase
{
    public function testCreateOpensTheTicketWithTheCustomersFirstMessage(): void
    {
        $customer = User::create('Ada Customer', 'ada@example.com', false, true);

        $ticket = $this->ticket($customer);

        self::assertSame(TicketStatus::OPEN, $ticket->getStatus());
        self::assertSame('Leaking airlock', $ticket->getSubject());
        self::assertSame(1, $ticket->getMessageCount());
        self::assertInstanceOf(\DateTimeImmutable::class, $ticket->getLastMessageAt());

        $message = $ticket->getMessages()->first();
        self::assertInstanceOf(Message::class, $message);
        self::assertSame($customer, $message->getAuthor());
        self::assertSame(AuthorType::CUSTOMER, $message->getAuthorType());
        self::assertSame(MessageVisibility::PUBLIC, $message->getVisibility());
    }

    public function testCreateTrimsTheSubject(): void
    {
        $ticket = Ticket::create(
            Pool::create('Support', null, true, true),
            User::create('Ada Customer', 'ada@example.com', false, true),
            '  Leaking airlock  ',
            'The seal hisses.',
        );

        self::assertSame('Leaking airlock', $ticket->getSubject());
    }

    public function testCreateFailsWithABlankSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Ticket subject cannot be empty');

        Ticket::create(
            Pool::create('Support', null, true, true),
            User::create('Ada Customer', 'ada@example.com', false, true),
            '   ',
            'The seal hisses.',
        );
    }

    public function testCloseThenReopen(): void
    {
        $ticket = $this->ticket();

        $ticket->close();
        self::assertSame(TicketStatus::CLOSED, $ticket->getStatus());

        $ticket->reopen();
        self::assertSame(TicketStatus::OPEN, $ticket->getStatus());
    }

    public function testCloseFailsWhenAlreadyClosed(): void
    {
        $ticket = $this->ticket();
        $ticket->close();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIsOrContains('Ticket is already closed.');

        $ticket->close();
    }

    public function testReopenFailsWhenNotClosed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIsOrContains('Ticket is not closed.');

        $this->ticket()->reopen();
    }

    public function testSnoozeUntilAFutureDate(): void
    {
        $ticket = $this->ticket();
        $until = new \DateTimeImmutable('+1 day');

        $ticket->snooze($until);

        self::assertTrue($ticket->isSnoozed());
        self::assertSame($until, $ticket->getSnoozedUntil());

        $ticket->unsnooze();

        self::assertFalse($ticket->isSnoozed());
        self::assertNull($ticket->getSnoozedUntil());
    }

    public function testSnoozeFailsForAPastDate(): void
    {
        $ticket = $this->ticket();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Snooze date must be in the future.');

        $ticket->snooze(new \DateTimeImmutable('-1 minute'));
    }

    /**
     * @return iterable<string, array{TicketStatus, AuthorType, TicketStatus}>
     */
    public static function replies(): iterable
    {
        yield 'staff reply to an open ticket' => [TicketStatus::OPEN, AuthorType::STAFF, TicketStatus::REPLIED];
        yield 'customer reply to a replied ticket' => [TicketStatus::REPLIED, AuthorType::CUSTOMER, TicketStatus::OPEN];
        yield 'customer reply to an open ticket' => [TicketStatus::OPEN, AuthorType::CUSTOMER, TicketStatus::OPEN];
        yield 'staff reply to a replied ticket' => [TicketStatus::REPLIED, AuthorType::STAFF, TicketStatus::REPLIED];
        yield 'system message on an open ticket' => [TicketStatus::OPEN, AuthorType::SYSTEM, TicketStatus::OPEN];
        yield 'system message' => [TicketStatus::REPLIED, AuthorType::SYSTEM, TicketStatus::REPLIED];
        yield 'staff reply to a closed ticket' => [TicketStatus::CLOSED, AuthorType::STAFF, TicketStatus::CLOSED];
        yield 'customer reply to a closed ticket' => [TicketStatus::CLOSED, AuthorType::CUSTOMER, TicketStatus::CLOSED];
    }

    #[DataProvider('replies')]
    public function testReplyMovesTheStatus(TicketStatus $from, AuthorType $author, TicketStatus $expected): void
    {
        $ticket = $this->ticket();
        match ($from) {
            TicketStatus::REPLIED => $ticket->transitionStatusForReply(AuthorType::STAFF),
            TicketStatus::CLOSED => $ticket->close(),
            TicketStatus::OPEN => null,
        };
        self::assertSame($from, $ticket->getStatus());

        $ticket->transitionStatusForReply($author);

        self::assertSame($expected, $ticket->getStatus());
    }

    public function testAddingAMessageCountsUpAndMovesTheLastMessageTime(): void
    {
        $ticket = $this->ticket();
        $first = $ticket->getLastMessageAt();

        $ticket->addMessage(Message::system($ticket, 'Auto reply'));

        self::assertSame(2, $ticket->getMessageCount());
        self::assertNotSame($first, $ticket->getLastMessageAt());
        self::assertGreaterThanOrEqual($first, $ticket->getLastMessageAt());
    }

    public function testRemovingAMessageTakesTheLastMessageTimeFromTheMessagesLeft(): void
    {
        $ticket = $this->ticket();
        $first = $ticket->getMessages()->first();
        \assert($first instanceof Message);
        $first->setCreatedAt(new \DateTime('2026-01-01 09:00'));

        $second = Message::system($ticket, 'Auto reply');
        $second->setCreatedAt(new \DateTime('2026-01-02 09:00'));

        $ticket->addMessage($second);

        $latest = Message::system($ticket, 'Follow up');
        $latest->setCreatedAt(new \DateTime('2026-01-03 09:00'));

        $ticket->addMessage($latest);

        $ticket->removeMessage($latest);

        self::assertSame(2, $ticket->getMessageCount());
        self::assertEquals(new \DateTimeImmutable('2026-01-02 09:00'), $ticket->getLastMessageAt());

        $ticket->removeMessage($first);
        $ticket->removeMessage($second);

        self::assertSame(0, $ticket->getMessageCount());
        self::assertNull($ticket->getLastMessageAt());
    }

    private function ticket(?User $customer = null): Ticket
    {
        return Ticket::create(
            Pool::create('Support', null, true, true),
            $customer ?? User::create('Ada Customer', 'ada@example.com', false, true),
            'Leaking airlock',
            'The seal on airlock 3 hisses at night.',
        );
    }
}
