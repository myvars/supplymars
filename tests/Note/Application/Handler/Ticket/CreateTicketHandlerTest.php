<?php

declare(strict_types=1);

namespace App\Tests\Note\Application\Handler\Ticket;

use App\Note\Application\Command\Ticket\CreateTicket;
use App\Note\Application\Handler\Ticket\CreateTicketHandler;
use App\Tests\Shared\Factory\MessageFactory;
use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateTicketHandlerTest extends KernelTestCase
{
    private CreateTicketHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateTicketHandler::class);
    }

    public function testHandleCreatesTicketWithFirstMessage(): void
    {
        $result = ($this->handler)(new CreateTicket(
            poolId: PoolFactory::createOne()->getId(),
            customerId: UserFactory::createOne()->getId(),
            subject: 'Damaged product',
            body: 'The packaging was crushed.',
        ));

        self::assertTrue($result->ok);
        TicketFactory::assert()->count(1);
        MessageFactory::assert()->count(1);
    }

    public function testHandleRefusesAnOverLengthFirstMessageFromTheEntityRules(): void
    {
        // No form in front of the handler: the message's own Length rule must stop the save.
        $result = ($this->handler)(new CreateTicket(
            poolId: PoolFactory::createOne()->getId(),
            customerId: UserFactory::createOne()->getId(),
            subject: 'Damaged product',
            body: str_repeat('a', 10001),
        ));

        self::assertFalse($result->ok);
        self::assertSame('Max 10000 characters', (string) $result->message);
        TicketFactory::assert()->empty();
        MessageFactory::assert()->empty();
    }
}
