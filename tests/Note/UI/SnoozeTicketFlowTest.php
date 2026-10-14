<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class SnoozeTicketFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ActionFormToken;

    public function testSnoozeTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($url)
            ->click('form[action="' . $url . '/snooze"] button')
            ->assertOn($url)
            ->assertSee('Ticket snoozed');
    }

    public function testToggleSnoozeUnsnoozesTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($url)
            ->click('form[action="' . $url . '/snooze"] button')
            ->assertSee('Ticket snoozed')
            ->click('form[action="' . $url . '/snooze"] button')
            ->assertOn($url)
            ->assertSee('Ticket unsnoozed');
    }

    public function testSnoozeIsNotReachableWithGet(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/snooze')
            ->assertStatus(405);

        self::assertFalse(TicketFactory::repository()->find($ticket->getId())?->isSnoozed());
    }

    public function testSnoozeRejectsAMissingTokenOrAnotherTicketsToken(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $other = TicketFactory::createOne(['pool' => $pool]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $otherUrl = '/note/ticket/' . $other->getPublicId()->value();
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, $otherUrl, $otherUrl . '/snooze');

        $browser
            ->post($url . '/snooze')
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.')
            ->post($url . '/snooze', ['body' => ['_token' => $otherToken]])
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.');

        self::assertFalse(TicketFactory::repository()->find($ticket->getId())?->isSnoozed());
    }
}
