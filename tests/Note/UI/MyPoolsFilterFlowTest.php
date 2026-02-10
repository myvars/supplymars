<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class MyPoolsFilterFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testMyPoolsFilterShowsOnlySubscribedPoolTickets(): void
    {
        $subscribedPool = PoolFactory::createOne(['isActive' => true, 'name' => 'Billing']);
        $otherPool = PoolFactory::createOne(['isActive' => true, 'name' => 'Shipping']);

        TicketFactory::createOne(['pool' => $subscribedPool, 'subject' => 'Billing inquiry']);
        TicketFactory::createOne(['pool' => $otherPool, 'subject' => 'Shipping delay']);

        $staff = UserFactory::new()->asStaff()->create();

        // Subscribe to Billing pool, then check My Pools filter
        $this->browser()
            ->actingAs($staff)
            ->get('/note/pool/' . $subscribedPool->getPublicId()->value() . '/subscribe')
            ->assertSee('Subscribed to Billing')
            ->get('/note/ticket/?myPools=1')
            ->assertSuccessful()
            ->assertSee('Billing inquiry')
            ->assertNotSee('Shipping delay');
    }

    public function testWithoutMyPoolsFilterShowsAllTickets(): void
    {
        $pool1 = PoolFactory::createOne(['isActive' => true, 'name' => 'Billing']);
        $pool2 = PoolFactory::createOne(['isActive' => true, 'name' => 'Shipping']);

        TicketFactory::createOne(['pool' => $pool1, 'subject' => 'Billing question']);
        TicketFactory::createOne(['pool' => $pool2, 'subject' => 'Shipping question']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/ticket/')
            ->assertSuccessful()
            ->assertSee('Billing question')
            ->assertSee('Shipping question');
    }
}
