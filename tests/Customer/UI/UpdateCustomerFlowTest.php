<?php

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateCustomerFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulEditViaForm(): void
    {
        $actor = UserFactory::new()->asStaff()->create();
        $customer = UserFactory::createOne(['fullName' => 'Before Edit', 'email' => 'before@example.com']);

        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs($actor)
            ->get('/customer/' . $publicId . '/edit')
            ->fillField('customer[fullName]', 'After Edit')
            ->fillField('customer[email]', 'after@example.com')
            ->uncheckField('customer[isVerified]')
            ->uncheckField('customer[isStaff]')
            ->click('Update Customer')
            ->assertOn('/customer/' . $publicId)
            ->assertSee('After Edit');
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $actor = UserFactory::new()->asStaff()->create();
        $customer = UserFactory::createOne(['fullName' => 'To Edit', 'email' => 'toedit@example.com']);

        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs($actor)
            ->get('/customer/' . $publicId . '/edit')
            ->fillField('customer[fullName]', '')
            ->click('Update Customer')
            ->assertOn('/customer/' . $publicId . '/edit')
            ->assertSee('Please enter a full name');
    }
}
