<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class CreateSupplierFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaForm(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/new')
            ->fillField('supplier[name]', 'Flow Supplier')
            ->fillField('supplier[isActive]', '1')
            ->click('Create Supplier')
            ->assertOn('/supplier/')
            ->assertSee('Flow Supplier');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/new')
            ->click('Create Supplier')
            ->assertOn('/supplier/new')
            ->assertSee('Please enter a supplier name');
    }
}
