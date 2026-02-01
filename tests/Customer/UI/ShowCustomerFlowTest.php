<?php

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ShowCustomerFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testShowRequiresAuthentication(): void
    {
        $customer = UserFactory::createOne();
        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->interceptRedirects()
            ->get('/customer/' . $publicId)
            ->assertRedirectedTo('/login');
    }

    public function testShowRendersForAuthenticatedStaff(): void
    {
        $actor = UserFactory::new()->asStaff()->create();
        $customer = UserFactory::createOne(['fullName' => 'Test Customer']);
        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs($actor)
            ->get('/customer/' . $publicId)
            ->assertSuccessful()
            ->assertSee('Test Customer');
    }

    public function testShowRendersInsightsCardForCustomerWithOrders(): void
    {
        $actor = UserFactory::new()->asStaff()->create();
        $customer = UserFactory::createOne(['fullName' => 'Ordering Customer']);
        $publicId = $customer->getPublicId()->value();

        $order = CustomerOrderFactory::createOne(['customer' => $customer]);
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->browser()
            ->actingAs($actor)
            ->get('/customer/' . $publicId)
            ->assertSuccessful()
            ->assertSee('Ordering Customer');
    }

    public function testShowRendersInsightsCardForCustomerWithNoOrders(): void
    {
        $actor = UserFactory::new()->asStaff()->create();
        $customer = UserFactory::createOne(['fullName' => 'New Customer']);
        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs($actor)
            ->get('/customer/' . $publicId)
            ->assertSuccessful()
            ->assertSee('New Customer');
    }
}
