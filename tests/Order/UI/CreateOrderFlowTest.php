<?php

namespace App\Tests\Order\UI;

use App\Shared\Domain\ValueObject\ShippingMethod;
use App\Tests\Shared\Factory\AddressFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class CreateOrderFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaForm(): void
    {
        $customer = UserFactory::createOne();
        AddressFactory::createOne([
            'customer' => $customer,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);
        VatRateFactory::new()->withStandardRate()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/new')
            ->fillField('order[customerId]', (string) $customer->getId())
            ->fillField('order[shippingMethod]', ShippingMethod::THREE_DAY->value)
            ->fillField('order[customerOrderRef]', 'FLOW-REF-001')
            ->click('Create Order')
            ->assertSuccessful()
            ->assertNotOn('/order/');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/new')
            ->click('Create Order')
            ->assertOn('/order/new')
            ->assertSee('Please enter a customer Id')
            ->assertSee('Please choose a shipping method');
    }
}
