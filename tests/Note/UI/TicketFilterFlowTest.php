<?php

namespace App\Tests\Note\UI;

use App\Note\Domain\Model\Ticket\TicketStatus;
use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class TicketFilterFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testFilterByStatusRedirectsWithParams(): void
    {
        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/search/filter')
            ->fillField('ticket_filter[status]', TicketStatus::OPEN->value)
            ->click('Apply Filter');

        $uri = $browser->crawler()->getUri();
        $query = [];
        parse_str((string) parse_url((string) $uri, PHP_URL_QUERY), $query);

        self::assertSame(strtolower(TicketStatus::OPEN->value), $query['status']);
        self::assertSame('on', $query['filter']);
    }

    public function testFilterByPoolRedirectsWithParams(): void
    {
        $pool = PoolFactory::createOne();

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/search/filter')
            ->fillField('ticket_filter[pool]', (string) $pool->getId())
            ->click('Apply Filter');

        $uri = $browser->crawler()->getUri();
        $query = [];
        parse_str((string) parse_url((string) $uri, PHP_URL_QUERY), $query);

        self::assertSame((string) $pool->getId(), $query['poolId']);
        self::assertSame('on', $query['filter']);
    }

    public function testFilterActiveIndicatorAppearsOnIndex(): void
    {
        TicketFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/?filter=on&status=open')
            ->assertSuccessful();
    }
}
