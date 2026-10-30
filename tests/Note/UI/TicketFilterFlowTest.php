<?php

declare(strict_types=1);

namespace App\Tests\Note\UI;

use App\Note\Domain\Model\Ticket\TicketStatus;
use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\FilterRedirect;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class TicketFilterFlowTest extends WebTestCase
{
    use FilterRedirect;
    use HasBrowser;

    public function testFilterByStatusRedirectsWithParams(): void
    {
        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/search/filter')
            ->fillField('ticket_filter[status]', TicketStatus::OPEN->value)
            ->click('Apply Filter');

        $this->assertRedirectedWithQuery($browser, '/note/ticket/', [
            'sort' => 'lastMessageAt',
            'sortDirection' => 'DESC',
            'page' => 1,
            'limit' => 5,
            'status' => strtolower(TicketStatus::OPEN->value),
            'filter' => 'on',
        ]);
    }

    public function testFilterByPoolRedirectsWithParams(): void
    {
        $pool = PoolFactory::createOne();

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/search/filter')
            ->fillField('ticket_filter[pool]', (string) $pool->getId())
            ->click('Apply Filter');

        $this->assertRedirectedWithQuery($browser, '/note/ticket/', [
            'sort' => 'lastMessageAt',
            'sortDirection' => 'DESC',
            'page' => 1,
            'limit' => 5,
            'poolId' => $pool->getId(),
            'filter' => 'on',
        ]);
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
