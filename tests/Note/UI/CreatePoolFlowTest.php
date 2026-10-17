<?php

declare(strict_types=1);

namespace App\Tests\Note\UI;

use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class CreatePoolFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testSuccessfulCreationViaForm(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/new')
            ->fillField('pool[name]', 'Product Queries')
            ->fillField('pool[description]', 'Questions about products')
            ->checkField('pool[isActive]')
            ->checkField('pool[isCustomerVisible]')
            ->click('Create Pool')
            ->assertSuccessful()
            ->assertSee('Product Queries')
            ->assertNotOn('/note/pool/');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/new')
            ->click('Create Pool')
            ->assertOn('/note/pool/new')
            ->assertSee('Please enter a pool name');
    }

    public function testRejectsOverLengthNameAsFieldError(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/new')
            ->fillField('pool[name]', str_repeat('a', 256))
            ->fillField('pool[description]', 'Questions about products')
            ->checkField('pool[isActive]')
            ->click('Create Pool')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 255 characters')
            ->assertNotSee('Object(');

        PoolFactory::assert()->empty();
    }

    public function testRejectsOverLengthDescriptionAsFieldError(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/note/pool/new')
            ->fillField('pool[name]', 'Product questions')
            ->fillField('pool[description]', str_repeat('a', 10001))
            ->checkField('pool[isActive]')
            ->click('Create Pool')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 10000 characters')
            ->assertNotSee('Object(');

        PoolFactory::assert()->empty();
    }
}
