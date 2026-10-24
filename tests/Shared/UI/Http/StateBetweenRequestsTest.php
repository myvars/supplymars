<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Shared\Domain\Event\DomainEventType;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\StatusChangeLogFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Mercure\MercureCookie;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

/**
 * Production runs FrankenPHP in worker mode: one kernel, and its services, serves many requests, so a
 * service that keeps a request's data in a property hands it to the next request, which may be
 * another user's. This test keeps one kernel too (disableReboot) and interleaves two logged-in users
 * and a visitor. See "State Between Requests" in Docs/03-architecture.md.
 */
class StateBetweenRequestsTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testEachResponseShowsOnlyItsOwnUser(): void
    {
        $ada = UserFactory::new()->asStaff()->create(['email' => 'ada@example.com', 'fullName' => 'Ada Lovelace', 'password' => 'ada-password']);
        $alan = UserFactory::new()->asStaff()->create(['email' => 'alan@example.com', 'fullName' => 'Alan Turing', 'password' => 'alan-password']);
        // Different "My Queue" counts, so a badge shown to the wrong user is caught
        $this->openTicketsFor($ada, 2);
        $this->openTicketsFor($alan, 5);
        $adaOrder = $this->order();
        $alanOrder = $this->order();

        $browser = $this->browser()->disableReboot();
        $adaCookies = $this->logIn($browser, 'ada@example.com', 'ada-password');
        $alanCookies = $this->logIn($browser, 'alan@example.com', 'alan-password');

        $this->switchTo($browser, $adaCookies);
        $this->cancel($browser, $adaOrder);
        $this->switchTo($browser, null);
        $browser->get('/order/')->assertOn('/login');
        $this->switchTo($browser, $alanCookies);
        $this->cancel($browser, $alanOrder);

        // Each status change is logged against whoever was logged in for the request that made it
        self::assertSame('ada@example.com', $this->cancelledBy($adaOrder)->getEmail());
        self::assertSame('alan@example.com', $this->cancelledBy($alanOrder)->getEmail());

        for ($round = 1; $round <= 3; ++$round) {
            $this->switchTo($browser, $adaCookies);
            $this->assertPageAs($browser, 'Ada Lovelace', 'ada@example.com', 'Alan Turing', 'alan@example.com', 2);
            $this->assertOrderPageAs($browser, $ada, $alan, $adaOrder);

            $this->switchTo($browser, null);
            $browser
                ->get('/order/')
                ->assertOn('/login')
                ->assertNotSee('ada@example.com')
                ->assertNotSee('alan@example.com')
                ->assertElementCount('turbo-mercure-stream-source', 0);
            self::assertNull(MercureCookie::find($browser));

            $this->switchTo($browser, $alanCookies);
            $this->assertPageAs($browser, 'Alan Turing', 'alan@example.com', 'Ada Lovelace', 'ada@example.com', 5);
            $this->assertOrderPageAs($browser, $alan, $ada, $alanOrder);
        }
    }

    /**
     * Logs in through the form, as a visitor would, and returns the cookies that carry the session.
     *
     * @return array<Cookie>
     */
    private function logIn(KernelBrowser $browser, string $email, string $password): array
    {
        $this->switchTo($browser, null);
        $browser
            ->visit('/login')
            ->fillField('email', $email)
            ->fillField('password', $password)
            ->click('Sign in')
            ->assertNotOn('/login');

        return $browser->client()->getCookieJar()->all();
    }

    /**
     * Sends the next requests with these cookies: one user's session, or none for a visitor.
     *
     * @param array<Cookie>|null $cookies
     */
    private function switchTo(KernelBrowser $browser, ?array $cookies): void
    {
        $jar = $browser->client()->getCookieJar();
        $jar->clear();
        foreach ($cookies ?? [] as $cookie) {
            $jar->set($cookie);
        }
    }

    private function openTicketsFor(User $user, int $count): void
    {
        $pool = PoolFactory::createOne();
        $pool->subscribe($user);
        TicketFactory::createMany($count, ['pool' => $pool]);
    }

    private function order(): CustomerOrder
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        return $order;
    }

    private function cancel(KernelBrowser $browser, CustomerOrder $order): void
    {
        $browser
            ->visit('/order/' . $order->getPublicId()->value() . '/cancel/confirm')
            ->click('Cancel Order')
            ->assertSee('Order cancelled');
    }

    private function cancelledBy(CustomerOrder $order): User
    {
        $log = StatusChangeLogFactory::repository()->findOneBy([
            'eventType' => DomainEventType::ORDER_STATUS_CHANGED,
            'eventTypeId' => $order->getId(),
        ]);
        self::assertInstanceOf(StatusChangeLog::class, $log);

        return $log->getUser();
    }

    private function assertPageAs(KernelBrowser $browser, string $name, string $email, string $otherName, string $otherEmail, int $myQueue): void
    {
        $browser
            ->get('/order/')
            ->assertSuccessful()
            // The header's user menu
            ->assertSee($name)
            ->assertSee($email)
            ->assertNotSee($otherName)
            ->assertNotSee($otherEmail)
            ->assertSeeIn('a[href$="myPools=1"] span', (string) $myQueue);
    }

    private function assertOrderPageAs(KernelBrowser $browser, User $viewer, User $other, CustomerOrder $order): void
    {
        $browser
            ->get('/order/' . $order->getPublicId()->value())
            ->assertSuccessful()
            // The page subscribes to the viewer's own toasts only
            ->assertElementCount('#user-notifications-' . $viewer->getPublicId(), 1)
            ->assertElementCount('#user-notifications-' . $other->getPublicId(), 0);

        // Its cookie lets the browser read the viewer's own topic and this order's, nothing else
        self::assertSame(
            ['subscribe' => ['user/' . $viewer->getPublicId(), CustomerOrder::liveTopic($order->getPublicId())]],
            MercureCookie::grantedTopics($browser),
        );
    }
}
