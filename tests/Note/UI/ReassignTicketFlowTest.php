<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ReassignTicketFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testReassignTicketToNewPool(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true, 'name' => 'Billing']);
        $newPool = PoolFactory::createOne(['isActive' => true, 'name' => 'Shipping']);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/reassign')
            ->assertSuccessful()
            ->assertSee('Reassign Ticket')
            ->selectFieldOption('reassign[pool]', (string) $newPool->getId())
            ->click('Reassign')
            ->assertOn('/note/ticket/' . $ticket->getPublicId()->value())
            ->assertSee('Ticket reassigned to Shipping')
            ->assertSee('Shipping')
            ->assertSee('reassigned from Billing to Shipping');
    }

    public function testReassignToSamePoolShowsError(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true, 'name' => 'Billing']);
        $ticket = TicketFactory::createOne(['pool' => $pool]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/' . $ticket->getPublicId()->value() . '/reassign')
            ->assertSuccessful()
            ->selectFieldOption('reassign[pool]', (string) $pool->getId())
            ->click('Reassign')
            ->assertSee('Ticket is already in this pool');
    }
}
