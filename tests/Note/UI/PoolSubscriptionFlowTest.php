<?php

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class PoolSubscriptionFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ActionFormToken;

    public function testSubscribeToPool(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true, 'name' => 'Billing']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $pool->getPublicId()->value())
            ->assertSuccessful()
            ->assertSee('You are not subscribed to this pool.')
            ->click('Subscribe')
            ->assertOn('/note/pool/' . $pool->getPublicId()->value())
            ->assertSee('Subscribed to Billing')
            ->assertSee('You are subscribed to this pool.')
            ->assertSee('Unsubscribe');
    }

    public function testToggleUnsubscribesFromPool(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true, 'name' => 'Shipping']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/' . $pool->getPublicId()->value())
            ->click('Subscribe')
            ->assertSee('Subscribed to Shipping')
            ->click('Unsubscribe')
            ->assertOn('/note/pool/' . $pool->getPublicId()->value())
            ->assertSee('Unsubscribed from Shipping')
            ->assertSee('You are not subscribed to this pool.')
            ->assertSee('Subscribe');
    }

    public function testSubscribeIsNotReachableWithGet(): void
    {
        $pool = PoolFactory::createOne(['isActive' => true]);
        $url = '/note/pool/' . $pool->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($url . '/subscribe')
            ->assertStatus(405)
            ->get($url)
            ->assertSee('You are not subscribed to this pool.');
    }

    public function testSubscribeRejectsAMissingTokenOrAnotherPoolsToken(): void
    {
        $other = PoolFactory::createOne(['isActive' => true]);
        $pool = PoolFactory::createOne(['isActive' => true]);
        $otherUrl = '/note/pool/' . $other->getPublicId()->value();
        $url = '/note/pool/' . $pool->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, $otherUrl, $otherUrl . '/subscribe');

        $browser
            ->post($url . '/subscribe')
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.')
            ->post($url . '/subscribe', ['body' => ['_token' => $otherToken]])
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.')
            ->assertSee('You are not subscribed to this pool.');
    }
}
