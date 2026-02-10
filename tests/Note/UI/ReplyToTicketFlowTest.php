<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ReplyToTicketFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testStaffCanReplyToTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSuccessful()
            ->assertSee('Reply')
            ->fillField('reply[body]', 'We are looking into this issue.')
            ->click('Send Reply')
            ->assertSuccessful()
            ->assertSee('Reply sent')
            ->assertSee('We are looking into this issue.');
    }

    public function testInternalReplyShowsInternalBadge(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSuccessful()
            ->fillField('reply[body]', 'This is an internal note.')
            ->selectFieldOption('reply[visibility]', 'INTERNAL')
            ->click('Send Reply')
            ->assertSuccessful()
            ->assertSee('Reply sent')
            ->assertSee('This is an internal note.')
            ->assertSee('Internal');
    }

    public function testEmptyReplyShowsValidationError(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSuccessful()
            ->fillField('reply[body]', '')
            ->click('Send Reply')
            ->assertSee('Please enter a message');
    }

    public function testReplyFormHiddenOnClosedTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $staff = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($staff)
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/close')
            ->assertSee('Ticket closed')
            ->assertNotContains('ticket-reply');
    }

    public function testReplyIncrementsMessageCount(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $ticketId = $ticket->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticketId)
            ->fillField('reply[body]', 'First reply')
            ->click('Send Reply')
            ->assertSuccessful()
            ->assertSee('Reply sent');

        $fresh = TicketFactory::repository()->find($ticket->getId());
        self::assertSame(2, $fresh->getMessageCount());
    }
}
