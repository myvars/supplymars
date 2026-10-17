<?php

declare(strict_types=1);

namespace App\Tests\Note\UI;

use App\Note\Domain\Model\Ticket\TicketStatus;
use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class TicketStatusFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ActionFormToken;

    public function testCloseTicketWithSystemMessage(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($url)
            ->click('form[action="' . $url . '/close"] button')
            ->assertOn($url)
            ->assertSee('Ticket closed')
            ->assertSee('CLOSED')
            ->assertSee('Ticket closed by');
    }

    public function testReopenTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($url)
            ->click('form[action="' . $url . '/close"] button')
            ->assertSee('Ticket closed')
            ->click('form[action="' . $url . '/reopen"] button')
            ->assertOn($url)
            ->assertSee('Ticket reopened')
            ->assertSee('OPEN')
            ->assertSee('Ticket reopened by');
    }

    public function testCannotCloseAlreadyClosedTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $token = $this->actionToken($browser, $url, $url . '/close');

        $browser
            ->click('form[action="' . $url . '/close"] button')
            ->assertSee('Ticket closed')
            ->post($url . '/close', ['body' => ['_token' => $token]])
            ->assertOn($url)
            ->assertSee('Ticket is already closed');
    }

    public function testCannotReopenNonClosedTicket(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::new()->closed()->create(['pool' => $pool]);
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $token = $this->actionToken($browser, $url, $url . '/reopen');

        $browser
            ->click('form[action="' . $url . '/reopen"] button')
            ->assertSee('Ticket reopened')
            ->post($url . '/reopen', ['body' => ['_token' => $token]])
            ->assertOn($url)
            ->assertSee('Ticket is not closed');
    }

    public function testCloseCreatesSystemMessage(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $initialCount = $ticket->getMessageCount();
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($url)
            ->click('form[action="' . $url . '/close"] button')
            ->assertSee('Ticket closed');

        $fresh = TicketFactory::repository()->find($ticket->getId());
        self::assertSame($initialCount + 1, $fresh->getMessageCount());
    }

    public function testReopenCreatesSystemMessage(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $staff = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($staff)
            ->get($url)
            ->click('form[action="' . $url . '/close"] button')
            ->assertSee('Ticket closed');

        $afterClose = TicketFactory::repository()->find($ticket->getId());
        $countAfterClose = $afterClose->getMessageCount();

        $this->browser()
            ->actingAs($staff)
            ->get($url)
            ->click('form[action="' . $url . '/reopen"] button')
            ->assertSee('Ticket reopened');

        $afterReopen = TicketFactory::repository()->find($ticket->getId());
        self::assertSame($countAfterClose + 1, $afterReopen->getMessageCount());
    }

    public function testCloseAndReopenAreNotReachableWithGet(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $open = TicketFactory::createOne(['pool' => $pool]);
        $closed = TicketFactory::new()->closed()->create(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $open->getPublicId()->value() . '/close')
            ->assertStatus(405)
            ->get('/note/ticket/' . $closed->getPublicId()->value() . '/reopen')
            ->assertStatus(405);

        self::assertSame(TicketStatus::OPEN, TicketFactory::repository()->find($open->getId())?->getStatus());
        self::assertSame(TicketStatus::CLOSED, TicketFactory::repository()->find($closed->getId())?->getStatus());
    }

    public function testCloseRejectsAMissingTokenOrAnotherTicketsToken(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $other = TicketFactory::createOne(['pool' => $pool]);
        $ticket = TicketFactory::createOne(['pool' => $pool]);
        $otherUrl = '/note/ticket/' . $other->getPublicId()->value();
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, $otherUrl, $otherUrl . '/close');

        $browser
            ->post($url . '/close')
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.')
            ->post($url . '/close', ['body' => ['_token' => $otherToken]])
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.');

        self::assertSame(TicketStatus::OPEN, TicketFactory::repository()->find($ticket->getId())?->getStatus());
    }

    public function testReopenRejectsAMissingTokenOrAnotherTicketsToken(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $other = TicketFactory::new()->closed()->create(['pool' => $pool]);
        $ticket = TicketFactory::new()->closed()->create(['pool' => $pool]);
        $otherUrl = '/note/ticket/' . $other->getPublicId()->value();
        $url = '/note/ticket/' . $ticket->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, $otherUrl, $otherUrl . '/reopen');

        $browser
            ->post($url . '/reopen')
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.')
            ->post($url . '/reopen', ['body' => ['_token' => $otherToken]])
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.');

        self::assertSame(TicketStatus::CLOSED, TicketFactory::repository()->find($ticket->getId())?->getStatus());
    }
}
