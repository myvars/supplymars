<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class SnoozeTicketFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSnoozeTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/snooze')
            ->assertOn('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSee('Ticket snoozed');
    }

    public function testToggleSnoozeUnsnoozesTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $staff = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($staff)
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/snooze')
            ->assertSee('Ticket snoozed')
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/snooze')
            ->assertOn('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSee('Ticket unsnoozed');
    }
}
