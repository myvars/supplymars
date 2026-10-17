<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Order\Domain\Model\Order\ShippingMethod;
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
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
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
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/order/new')
            ->click('Create Order')
            ->assertOn('/order/new')
            ->assertSee('Please enter a customer Id')
            ->assertSee('Please choose a shipping method');
    }

    public function testValidationErrorsOnUnknownCustomerId(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/order/new')
            ->fillField('order[customerId]', '99999')
            ->fillField('order[shippingMethod]', ShippingMethod::THREE_DAY->value)
            ->click('Create Order')
            ->assertOn('/order/new')
            ->assertSee('Customer with Id "99999" not found');
    }

    public function testValidationErrorsOnNegativeCustomerId(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/order/new')
            ->fillField('order[customerId]', '-1')
            ->fillField('order[shippingMethod]', ShippingMethod::THREE_DAY->value)
            ->click('Create Order')
            ->assertOn('/order/new')
            ->assertSee('Please enter a valid customer Id')
            ->assertSee('Customer with Id "-1" not found');
    }
}
