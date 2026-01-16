<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateSupplierFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulEditViaForm(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Before Edit', 'isActive' => true]);
        $publicId = $supplier->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $publicId . '/edit')
            ->fillField('supplier[name]', 'After Edit')
            ->uncheckField('supplier[isActive]')
            ->click('Update Supplier')
            ->assertOn('/supplier/')
            ->assertSee('After Edit');
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Will Fail', 'isActive' => true]);
        $publicId = $supplier->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $publicId . '/edit')
            ->fillField('supplier[name]', '')
            ->click('Update Supplier')
            ->assertOn('/supplier/' . $publicId . '/edit')
            ->assertSee('Please enter a supplier name');
    }
}
