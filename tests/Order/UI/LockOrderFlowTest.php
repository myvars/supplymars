<?php

namespace App\Tests\Order\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class LockOrderFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testLockOrderViaToggle(): void
    {
        $order = CustomerOrderFactory::createOne();
        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId . '/lock/toggle')
            ->assertOn('/order/' . $publicId)
            ->assertSeeElement('a[data-order-lock="locked"]');
    }

    public function testUnlockOrderViaToggle(): void
    {
        $order = CustomerOrderFactory::createOne();
        $publicId = $order->getPublicId()->value();

        $staff = UserFactory::new()->asStaff()->create();

        // Lock the order first
        $this->browser()
            ->actingAs($staff)
            ->get('/order/' . $publicId . '/lock/toggle')
            ->assertOn('/order/' . $publicId)
            ->assertSeeElement('a[data-order-lock="locked"]')
            ->get('/order/' . $publicId . '/lock/toggle')
            ->assertOn('/order/' . $publicId)
            ->assertSeeElement('a[data-order-lock="unlocked"]');
    }
}
