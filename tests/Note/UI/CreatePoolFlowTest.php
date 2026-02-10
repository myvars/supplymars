<?php

namespace App\Tests\Note\UI;

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
}
