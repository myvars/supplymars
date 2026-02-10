<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class PoolSubscriptionFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSubscribeToPool(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true, 'name' => 'Billing']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $pool->getPublicId()->value())
            ->assertSuccessful()
            ->assertSee('You are not subscribed to this pool.')
            ->assertSee('Subscribe')
            ->get('/note/pool/' . $pool->getPublicId()->value() . '/subscribe')
            ->assertOn('/note/pool/' . $pool->getPublicId()->value())
            ->assertSee('Subscribed to Billing')
            ->assertSee('You are subscribed to this pool.')
            ->assertSee('Unsubscribe');
    }

    public function testToggleUnsubscribesFromPool(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true, 'name' => 'Shipping']);

        $staff = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($staff)
            ->get('/note/pool/' . $pool->getPublicId()->value() . '/subscribe')
            ->assertSee('Subscribed to Shipping')
            ->get('/note/pool/' . $pool->getPublicId()->value() . '/subscribe')
            ->assertOn('/note/pool/' . $pool->getPublicId()->value())
            ->assertSee('Unsubscribed from Shipping')
            ->assertSee('You are not subscribed to this pool.')
            ->assertSee('Subscribe');
    }
}
