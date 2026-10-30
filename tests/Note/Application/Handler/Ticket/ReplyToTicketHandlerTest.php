<?php

declare(strict_types=1);

namespace App\Tests\Note\Application\Handler\Ticket;

use App\Note\Application\Command\Ticket\ReplyToTicket;
use App\Note\Application\Handler\Ticket\ReplyToTicketHandler;
use App\Note\Domain\Model\Message\AuthorType;
use App\Note\Domain\Model\Message\MessageVisibility;
use App\Tests\Shared\Factory\MessageFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReplyToTicketHandlerTest extends KernelTestCase
{
    private ReplyToTicketHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(ReplyToTicketHandler::class);
    }

    public function testHandleAddsReply(): void
    {
        $ticket = TicketFactory::createOne();

        $result = ($this->handler)(new ReplyToTicket(
            ticketId: $ticket->getPublicId(),
            authorId: UserFactory::new()->asStaff()->create()->getId(),
            authorType: AuthorType::STAFF,
            body: 'We are looking into it.',
            visibility: MessageVisibility::PUBLIC,
        ));

        self::assertTrue($result->ok);
        MessageFactory::assert()->count(2);
    }

    public function testHandleSavesAReplyAtTheLimitInFourByteCharacters(): void
    {
        // 10,000 four-byte characters is 40,000 bytes: the limit must fit the TEXT column.
        $ticket = TicketFactory::createOne();
        $body = str_repeat('🚀', 10000);

        $result = ($this->handler)(new ReplyToTicket(
            ticketId: $ticket->getPublicId(),
            authorId: UserFactory::new()->asStaff()->create()->getId(),
            authorType: AuthorType::STAFF,
            body: $body,
            visibility: MessageVisibility::PUBLIC,
        ));

        self::assertTrue($result->ok);
        MessageFactory::assert()->exists(['body' => $body]);
    }

    public function testHandleRefusesAnOverLengthReplyFromTheEntityRules(): void
    {
        // No form in front of the handler: the message's own Length rule must stop the save.
        $ticket = TicketFactory::createOne();

        $result = ($this->handler)(new ReplyToTicket(
            ticketId: $ticket->getPublicId(),
            authorId: UserFactory::new()->asStaff()->create()->getId(),
            authorType: AuthorType::STAFF,
            body: str_repeat('a', 10001),
            visibility: MessageVisibility::PUBLIC,
        ));

        self::assertFalse($result->ok);
        self::assertSame('Max 10000 characters', (string) $result->message);
        MessageFactory::assert()->count(1);
    }

    public function testHandleRefusesReplyToClosedTicket(): void
    {
        $ticket = TicketFactory::new()->closed()->create();

        $result = ($this->handler)(new ReplyToTicket(
            ticketId: $ticket->getPublicId(),
            authorId: UserFactory::new()->asStaff()->create()->getId(),
            authorType: AuthorType::STAFF,
            body: 'Too late.',
            visibility: MessageVisibility::PUBLIC,
        ));

        self::assertFalse($result->ok);
        self::assertSame('Ticket is closed. Reopen it to reply.', $result->message);
        MessageFactory::assert()->notExists(['body' => 'Too late.']);
    }
}
