<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class TicketStatusFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testCloseTicketWithSystemMessage(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/close')
            ->assertOn('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSee('Ticket closed')
            ->assertSee('CLOSED')
            ->assertSee('Ticket closed by');
    }

    public function testReopenTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $staff = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($staff)
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/close')
            ->assertSee('Ticket closed')
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/reopen')
            ->assertOn('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSee('Ticket reopened')
            ->assertSee('OPEN')
            ->assertSee('Ticket reopened by');
    }

    public function testCannotCloseAlreadyClosedTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $staff = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($staff)
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/close')
            ->assertSee('Ticket closed')
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/close')
            ->assertOn('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSee('Ticket is already closed');
    }

    public function testCannotReopenNonClosedTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/reopen')
            ->assertOn('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSee('Ticket is not closed');
    }

    public function testCloseCreatesSystemMessage(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $initialCount = $ticket->getMessageCount();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/close')
            ->assertSee('Ticket closed');

        $fresh = TicketFactory::repository()->find($ticket->getId());
        self::assertSame($initialCount + 1, $fresh->getMessageCount());
    }

    public function testReopenCreatesSystemMessage(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $staff = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($staff)
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/close')
            ->assertSee('Ticket closed');

        $afterClose = TicketFactory::repository()->find($ticket->getId());
        $countAfterClose = $afterClose->getMessageCount();

        $this->browser()
            ->actingAs($staff)
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/reopen')
            ->assertSee('Ticket reopened');

        $afterReopen = TicketFactory::repository()->find($ticket->getId());
        self::assertSame($countAfterClose + 1, $afterReopen->getMessageCount());
    }
}
