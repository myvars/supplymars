<?php

namespace App\Tests\Order\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ShowOrderFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testShowPageRendersBreadcrumbAndDescriptionList(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);
        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->assertSuccessful()
            ->assertSee('Orders')
            ->assertSee('Order #')
            ->assertSeeElement('nav[aria-label="Breadcrumb"]')
            ->assertSeeElement('dl');
    }
}
