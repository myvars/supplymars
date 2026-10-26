<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Mercure\MercureCookie;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

/** Each staff page subscribes to its own user's toasts, and only to those (base.html.twig). A customer's subscribes to nothing. */
class UserNotificationSubscriptionFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testLoggedInPageSubscribesToItsOwnTopicOnly(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $browser = $this->browser()
            ->actingAs($user)
            ->visit('/')
            ->assertSuccessful()
            // The id names the user: Turbo keeps a permanent element only for a page with the same id
            ->assertElementCount('#user-notifications-' . $user->getPublicId() . '[data-turbo-permanent] turbo-mercure-stream-source[private]', 1)
            // The dashboard declares no live topics, so Turbo may cache it
            ->assertElementCount('turbo-mercure-stream-source', 1)
            ->assertElementCount('meta[name="turbo-cache-control"]', 0)
            ->assertElementCount('meta[name="turbo-prefetch"]', 0)
            // A live refresh's morph keeps the toaster and adds new flashes (live-toaster)
            ->assertElementCount('div[data-controller="live-toaster"] #flash-container', 1);

        $src = $browser->crawler()->filter('turbo-mercure-stream-source')->attr('src');
        self::assertIsString($src);
        self::assertStringContainsString(rawurlencode('user/' . $user->getPublicId()), $src);

        $cookie = MercureCookie::find($browser);
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame('/.well-known/mercure', $cookie->getPath());
        // It lasts a week (app.login_lifetime), not an hour: the hub closes a connection when its token expires,
        // which would silence a tab left on one page
        self::assertEqualsWithDelta(time() + 604800, $cookie->getExpiresTime(), 60);
        self::assertEqualsWithDelta(time() + 604800, MercureCookie::expiresAt($browser), 60);

        // The token lets this browser subscribe to this user's topic and nothing else, and publish nothing
        self::assertSame(['subscribe' => ['user/' . $user->getPublicId()]], MercureCookie::grantedTopics($browser));
    }

    public function testCustomerPageHasNoSubscription(): void
    {
        // A customer receives no toasts and views no live page. Without a cookie the hub refuses every topic,
        // including the public refreshes of the order pages
        $browser = $this->browser()
            ->actingAs(UserFactory::createOne())
            ->visit('/')
            ->assertSuccessful()
            ->assertElementCount('turbo-mercure-stream-source', 0);

        self::assertNull(MercureCookie::find($browser));
    }

    public function testAnonymousPageHasNoSubscription(): void
    {
        $browser = $this->browser()
            ->visit('/login')
            ->assertSuccessful()
            ->assertElementCount('turbo-mercure-stream-source', 0);

        self::assertNull(MercureCookie::find($browser));
    }

    public function testLogoutClearsTheCookie(): void
    {
        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/')
            ->interceptRedirects()
            ->visit('/logout')
            ->assertRedirected();

        $cookie = MercureCookie::find($browser);
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isCleared());
        self::assertSame('/.well-known/mercure', $cookie->getPath());
    }
}
