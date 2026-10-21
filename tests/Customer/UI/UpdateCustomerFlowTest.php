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

    public function testRejectsEmailInUseAsFieldError(): void
    {
        UserFactory::createOne(['email' => 'taken@example.com']);
        $customer = UserFactory::createOne(['fullName' => 'Before Edit', 'email' => 'before@example.com']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $customer->getPublicId()->value() . '/edit')
            ->fillField('customer[email]', 'taken@example.com')
            ->click('Update Customer')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'There is already an account with this email')
            ->assertNotSee('Object(');

        self::assertSame('before@example.com', $customer->getEmail());
    }

    public function testKeepingOwnEmailIsNotADuplicate(): void
    {
        $customer = UserFactory::createOne(['fullName' => 'Before Edit', 'email' => 'mine@example.com']);
        $publicId = $customer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $publicId . '/edit')
            ->fillField('customer[fullName]', 'After Edit')
            ->click('Update Customer')
            ->assertOn('/customer/' . $publicId)
            ->assertSee('After Edit');
    }

    public function testInlineEditRejectsOverLengthFullName(): void
    {
        $entity = UserFactory::createOne(['fullName' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $entity->getPublicId()->value() . '/inline/fullname?edit=1')
            ->fillField('inline_field[value]', str_repeat('a', 51))
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Max 50 characters') // the field's error, rendered inside the form
            ->assertSeeElement('input[name="inline_field[value]"]');

        self::assertSame('Old Value', $entity->getFullName());
    }

    public function testInlineEditRejectsBlankFullName(): void
    {
        $entity = UserFactory::createOne(['fullName' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/customer/' . $entity->getPublicId()->value() . '/inline/fullname?edit=1')
            ->fillField('inline_field[value]', '')
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Please enter a full name');

        self::assertSame('Old Value', $entity->getFullName());
    }
}
