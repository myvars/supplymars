<?php

namespace App\Tests\Purchasing\UI\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use tests\Shared\Factory\SupplierFactory;
use tests\Shared\Factory\UserFactory;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class SupplierControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexSupplier(): void
    {
        SupplierFactory::createMany(3);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/')
            ->assertSuccessful()
            ->assertSee('Supplier Search')
            ->assertSee('3 results');
    }

    public function testSupplierSecurity(): void
    {
        $this->browser()
            ->get('/supplier/')
            ->assertOn('/login');
    }

    public function testShowSupplier(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Test Supplier']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $supplier->getId())
            ->assertSuccessful()
            ->assertSee('Test Supplier');
    }

    public function testNewSupplier(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/new')
            ->assertSuccessful()
            ->fillField('supplier[name]', 'Test Supplier')
            ->fillField('supplier[isActive]', '1')
            ->click('Create Supplier')
            ->assertOn('/supplier/')
            ->assertSee('Test Supplier');
    }

    public function testNewSupplierValidation(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/new')
            ->assertSuccessful()
            // Intentionally omitting form data or filling it with invalid data
            ->click('Create Supplier')
            ->assertOn('/supplier/new')
            ->assertSee('Please enter a supplier name');
    }

    public function testEditSupplier(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Supplier to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $supplier->getId() . '/edit')
            ->assertSuccessful()
            ->fillField('supplier[name]', 'Edited Supplier')
            ->click('Update Supplier')
            ->assertOn('/supplier/')
            ->assertSee('Edited Supplier');
    }

    public function testEditSupplierEditValidation(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Supplier to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $supplier->getId() . '/edit')
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('supplier[name]', '')
            ->click('Update Supplier')
            ->assertOn('/supplier/' . $supplier->getId() . '/edit')
            ->assertSee('Please enter a supplier name');
    }

    public function testDeleteSupplierConfirmation(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Supplier to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $supplier->getId() . '/delete/confirm')
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Supplier');
    }

    public function testDeleteSupplier(): void
    {
        $supplier = SupplierFactory::createone(['name' => 'Supplier to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $supplier->getId() . '/delete/confirm')
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/supplier/')
            ->assertNotSee('Supplier to be deleted');
    }

    public function testMissingSupplier(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/999')
            ->assertSee('Supplier not found!');
    }
}
