<?php

namespace App\Tests\Order\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class LockOrderFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ActionFormToken;

    public function testLockOrderViaToggle(): void
    {
        $order = CustomerOrderFactory::createOne();
        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->click('button[data-order-lock]')
            ->assertOn('/order/' . $publicId)
            ->assertSeeElement('button[data-order-lock="locked"]');
    }

    public function testUnlockOrderViaToggle(): void
    {
        $order = CustomerOrderFactory::createOne();
        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->click('button[data-order-lock]')
            ->assertOn('/order/' . $publicId)
            ->assertSeeElement('button[data-order-lock="locked"]')
            ->click('button[data-order-lock]')
            ->assertOn('/order/' . $publicId)
            ->assertSeeElement('button[data-order-lock="unlocked"]');
    }

    public function testLockIsNotReachableWithGet(): void
    {
        $publicId = CustomerOrderFactory::createOne()->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId . '/lock/toggle')
            ->assertStatus(405)
            ->get('/order/' . $publicId)
            ->assertSeeElement('button[data-order-lock="unlocked"]');
    }

    public function testLockRejectsAMissingTokenOrAnotherOrdersToken(): void
    {
        $other = CustomerOrderFactory::createOne()->getPublicId()->value();
        $publicId = CustomerOrderFactory::createOne()->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, '/order/' . $other, '/order/' . $other . '/lock/toggle');

        $browser
            ->post('/order/' . $publicId . '/lock/toggle')
            ->assertOn('/order/' . $publicId)
            ->assertSee('Invalid CSRF token.')
            ->post('/order/' . $publicId . '/lock/toggle', ['body' => ['_token' => $otherToken]])
            ->assertOn('/order/' . $publicId)
            ->assertSee('Invalid CSRF token.')
            ->assertSeeElement('button[data-order-lock="unlocked"]');
    }
}
