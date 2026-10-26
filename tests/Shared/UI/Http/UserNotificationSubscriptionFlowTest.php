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

    public function testCustomerLoginClearsACookieLeftByStaff(): void
    {
        UserFactory::createOne(['email' => 'customer@example.com', 'password' => 'customer-password']);

        // Staff use the browser and leave without logging out, so their cookie stays
        $staff = UserFactory::new()->asStaff()->create();
        $browser = $this->browser()
            ->actingAs($staff)
            ->visit('/');
        self::assertSame(['subscribe' => ['user/' . $staff->getPublicId()]], MercureCookie::grantedTopics($browser));

        // A customer then signs in on it: their pages set no cookie, so the login must clear the staff one
        $browser
            ->visit('/login')
            ->fillField('email', 'customer@example.com')
            ->fillField('password', 'customer-password')
            ->interceptRedirects()
            ->click('Sign in')
            ->assertRedirected();

        $cookie = MercureCookie::find($browser);
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isCleared());
    }

    public function testStaffRememberMeLoginSetsTheCookie(): void
    {
        $staff = UserFactory::new()->asStaff()->create(['password' => 'staff-password']);
        $remembered = $this->browser()
            ->visit('/login')
            ->fillField('email', $staff->getEmail())
            ->fillField('password', 'staff-password')
            ->click('Sign in')
            ->client()->getCookieJar()->get('REMEMBERME');
        self::assertNotNull($remembered);

        // A new visit with only the remember-me cookie logs in and renders the page in one request, which sets the
        // cookie: the login listener must leave it alone, or setting it would throw
        $browser = $this->browser();
        $browser->client()->getCookieJar()->set($remembered);
        $browser->visit('/')->assertSuccessful();

        self::assertSame(['subscribe' => ['user/' . $staff->getPublicId()]], MercureCookie::grantedTopics($browser));
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
