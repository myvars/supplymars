<?php

namespace App\Tests\Customer\UI\Http;

use tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class CustomerControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexCustomer(): void
    {
        UserFactory::createMany(3);
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get('/customer/')
            ->assertSuccessful()
            ->assertSee('Customer Search')
            ->assertSee('4 results');
    }

    public function testCustomerSecurity(): void
    {
        $this->browser()
            ->get('/customer/')
            ->assertOn('/login');
    }

    public function testShowCustomer(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Test Customer']);
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get("/customer/" . $customer->getPublicId())
            ->assertSuccessful()
            ->assertSee('Test Customer');
    }

    public function testEditCustomer(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Customer to be edited']);
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get("/customer/" . $customer->getPublicId() . "/edit")
            ->assertSuccessful()
            ->fillField('customer[fullName]', 'Edited Customer')
            ->click('Update Customer')
            ->assertOn('/customer/')
            ->assertSee('Edited Customer');
    }

    public function testEditCustomerValidation(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Customer to be edited']);
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get("/customer/" . $customer->getId() . "/edit")
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('customer[fullName]','')
            ->fillField('customer[email]', '')
            ->click('Update Customer')
            ->assertOn("/customer/" . $customer->getId() . "/edit")
            ->assertSee('Please enter a full name')
            ->assertSee('Please enter a valid email');
    }

    public function testEditCustomerWithExistingEmail(): void
    {
        UserFactory::createOne(['email' => 'customer@exists.com']);
        $customer = UserFactory::createOne(['fullName' => 'Customer to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/customer/" . $customer->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('customer[email]', 'customer@exists.com')
            ->click('Update Customer')
            ->assertOn("/customer/" . $customer->getId() . "/edit")
            ->assertSee('There is already an account with this email');
    }

    public function testDeleteCustomerConfirmation(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Customer to be deleted']);
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get("/customer/" . $customer->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Customer');
    }

    public function testDeleteCustomer(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Customer to be deleted']);
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get("/customer/" . $customer->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/customer/')
            ->assertNotSee('Customer to be deleted');
    }

    public function testCustomerNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/customer/999")
            ->assertSee("Customer not found!");
    }
}
