<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class RegistrationFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testRejectsOverLengthEmailAsFieldError(): void
    {
        // A valid address of 212 characters, over the 180 the column holds
        $email = 'user@' . implode('.', array_fill(0, 4, str_repeat('a', 50))) . '.com';

        $this->browser()
            ->get('/register')
            ->fillField('registration_form[fullName]', 'New Customer')
            ->fillField('registration_form[email]', $email)
            ->checkField('registration_form[agreeTerms]')
            ->fillField('registration_form[plainPassword][first]', 'correct-horse-battery')
            ->fillField('registration_form[plainPassword][second]', 'correct-horse-battery')
            ->click('Create account')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 180 characters')
            ->assertNotSee('Object(');

        UserFactory::assert()->empty();
    }

    public function testRejectsEmailInUseAsFieldError(): void
    {
        UserFactory::createOne(['email' => 'taken@example.com']);

        $this->browser()
            ->get('/register')
            ->fillField('registration_form[fullName]', 'New Customer')
            ->fillField('registration_form[email]', 'taken@example.com')
            ->checkField('registration_form[agreeTerms]')
            ->fillField('registration_form[plainPassword][first]', 'correct-horse-battery')
            ->fillField('registration_form[plainPassword][second]', 'correct-horse-battery')
            ->click('Create account')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'There is already an account with this email')
            ->assertNotSee('Object(');

        UserFactory::assert()->count(1);
    }
}
