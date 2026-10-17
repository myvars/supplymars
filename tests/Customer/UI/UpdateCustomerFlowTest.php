<?php

declare(strict_types=1);

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

    public function testRejectsOverLengthEmailAsFieldError(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Before Edit', 'email' => 'before@example.com']);
        // A valid address of 212 characters, over the 180 the column holds
        $email = 'user@' . implode('.', array_fill(0, 4, str_repeat('a', 50))) . '.com';

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $customer->getPublicId()->value() . '/edit')
            ->fillField('customer[email]', $email)
            ->click('Update Customer')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 180 characters')
            ->assertNotSee('Object(');

        self::assertSame('before@example.com', $customer->getEmail());
    }
}
